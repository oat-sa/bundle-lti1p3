# LTI Service - Platform

> How to use the bundle to make your application act as a platform in the context of [LTI services](http://www.imsglobal.org/spec/lti/v1p3/#interacting-with-services).

## Table of contents

- [Providing platform service access token endpoint](#providing-platform-service-access-token-endpoint)
- [Protecting platform service endpoints](#protecting-platform-service-endpoints)
- [Providing platform service endpoints using the LTI libraries](#providing-platform-service-endpoints-using-the-lti-libraries)

## Providing platform service access token endpoint

The [OAuth2AccessTokenCreationAction](../../Action/Platform/Service/OAuth2AccessTokenCreationAction.php) is automatically added to your application via the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

**Default route**: `[POST] '/lti1p3/auth/{keyChainIdentifier}/token'`

This endpoint:
- allow tools to get granted to call your platform services endpoints, by following the [client_credentials grant type with assertion](https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant). 
- is working for a defined `keyChainIdentifier` as explained [here](../message/platform.md), so you can expose several of them if your application is acting as several deployed platforms
- is able to grant (give access tokens) for a defined list of allowed scopes 

You must first configure the list of allowed scopes to grant access tokens:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    scopes:
        - 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem'
        - 'https://purl.imsglobal.org/spec/lti-ags/scope/result/read'
```

Then, if you configure a key chain as following:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        platformKey:
            key_set_name: "platformSet"
            public_key: "file://path/to/public.key"
            private_key: "file://path/to/private.key"
            private_key_passphrase: 'someSecretPassPhrase'
```

You can then configure a platform as following (using the key chain identifier `platformKey`):

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    platforms:
        myPlatform:
            name: "My Platform"
            audience: "http://platform.com"
            oidc_authentication_url: "http://platform.com/lti1p3/oidc/authentication"
            oauth2_access_token_url: "http://platform.com/lti1p3/auth/platformKey/token"
```

Once set up, tools can request access tokens by following the [client_credentials grant type with assertion](https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant):
- `grant_type`: `client_credentials`
- `client_assertion_type`: `urn:ietf:params:oauth:client-assertion-type:jwt-bearer`
- `client_assertion`: the tool's generated JWT assertion
- `scope`: `https://purl.imsglobal.org/spec/lti-ags/scope/lineitem https://purl.imsglobal.org/spec/lti-ags/scope/result/read`

Request example:

```shell script
POST /lti1p3/auth/platformKey/token HTTP/1.1
Host: example.com
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
&client_assertion_type=urn%3Aietf%3Aparams%3Aoauth%3Aclient-assertion-type%3Ajwt-bearer
&client_assertion=eyJ0eXAiOi....
&scope=http%3A%2F%2Fimsglobal.org%2Fspec%2Flti-ags%2Fscope%2Flineitem%20http%3A%2F%2Fimsglobal.org%2Fspec%2Flti-ags%2Fscope%2Fresult%2Fread 
```

As a response, the [OAuth2AccessTokenCreationAction](../../Action/Platform/Service/OAuth2AccessTokenCreationAction.php) will offer an access token (following OAuth2 standards), valid for `3600 seconds`:

```shell script
HTTP/1.1 200 OK
Content-Type: application/json;charset=UTF-8
Cache-Control: no-store
Pragma: no-cache

{
    "access_token" : "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1N.....",
    "token_type" : "bearer",
    "expires_in" : 3600,
    "scope" : "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem https://purl.imsglobal.org/spec/lti-ags/scope/result/read"    
} 
```

**Notes**:
- a `HTTP 400` response is returned if the requested scopes are not configured, or invalid
- a `HTTP 401` response is returned if the client assertion cannot match a registered tool
- to automate (and cache) authentication grants from the tools side, a [LtiServiceClient](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Service/Client/LtiServiceClient.php) is ready to use for your LTI service calls as explained [here](tool.md)

## Protecting platform service endpoints

For example, considering you have the following platform service endpoints:

```yaml
#config/routes.yaml
platform_service_ags_lineitem:
    path: /platform/service/ags/lineitem
    controller: App\Action\Platform\Service\Ags\LineItemAction
platform_service_ags_result:
    path: /platform/service/ags/result
    controller: App\Action\Platform\Service\Ags\ResultAction
```

To protect your endpoint, this bundle provides the `lti1p3_service` [security firewall](../../Security/Firewall/Service/LtiServiceAuthenticator.php) to put in front of your routes:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        secured_service_ags_lineitem_area:
            pattern: ^/platform/service/ags/lineitem
            stateless: true
            lti1p3_service: { scopes: ['https://purl.imsglobal.org/spec/lti-ags/scope/lineitem'] }
        secured_service_ags_result_area:
            pattern: ^/platform/service/ags/result
            stateless: true
            lti1p3_service: { scopes: ['https://purl.imsglobal.org/spec/lti-ags/scope/result/read'] }
```

**Note**: you can define per firewall the list of allowed scopes, to have better granularity for your endpoints protection.

It will:
- handle the provided access token validation (signature validity, expiry, matching configured firewall scopes, etc ...)
- add on success a [LtiServiceSecurityToken](../../Security/Authentication/Token/Service/LtiServiceSecurityToken.php) in the [security token storage](https://symfony.com/doc/current/security.html), that you can use to retrieve your authentication context

For example (in one of the endpoints):

```php
<?php

declare(strict_types=1);

namespace App\Action\Platform\Service\Ags;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;

class LineItemAction
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        /** @var LtiServiceSecurityToken $token */
        $token = $this->security->getToken();

        // Related registration (to spare queries)
        $registration = $token->getRegistration();

        // Related access token
        $token = $token->getAccessToken();

        // Related scopes (if you want to implement some ACL)
        $scopes = $token->getScopes(); // ['https://purl.imsglobal.org/spec/lti-ags/scope/lineitem']

        // You can even access validation results
        $validationResults = $token->getValidationResult();

        // Your service endpoint logic ...

        return new Response(...);
    }
}
```

## Providing platform service endpoints using the LTI libraries

We provide a [collection of LTI libraries](https://github.com/oat-sa?q=lti1p3&type=&language=&sort=) to offer LTI capabilities (NRPS, AGS, basic outcomes, etc) to your application.

The bundle provides a way to easily integrate them when it comes to expose LTI services endpoints:
- the core [LtiServiceServerRequestHandlerInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Service/Server/Handler/LtiServiceServerRequestHandlerInterface.php) is implemented by those libraries to provide their service endpoints logic
- the bundle [LtiServiceServerHttpFoundationRequestHandlerInterface](../../Service/Server/Handler/LtiServiceServerHttpFoundationRequestHandlerInterface.php) symfony service automates any `LtiServiceServerRequestHandlerInterface` implementation execution
- the bundle [LtiServiceServerHttpFoundationRequestHandlerFactoryInterface](../../Service/Server/Factory/LtiServiceServerHttpFoundationRequestHandlerFactoryInterface.php) symfony service can be used to ease the `LtiServiceServerHttpFoundationRequestHandlerInterface` creation (symfony service factory)

For example, let's implement step by step the [NRPS library](https://github.com/oat-sa/lib-lti1p3-nrps/blob/master/src/Service/Server/Handler/MembershipServiceServerRequestHandler.php) membership service endpoint into your application:

- install the library

```console
$ composer require oat-sa/lib-lti1p3-nrps
```

- allow NRPS scope

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    scopes:
        - 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'
```

- provide a required [MembershipServiceServerBuilderInterface](https://github.com/oat-sa/lib-lti1p3-nrps/blob/master/doc/platform.md#usage) implementation

```php
<?php

declare(strict_types=1);

namespace App\Nrps;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use OAT\Library\Lti1p3Nrps\Service\Server\Builder\MembershipServiceServerBuilderInterface;

class MembershipServiceServerBuilder implements MembershipServiceServerBuilderInterface 
{
    public function buildContextMembership(
        RegistrationInterface $registration,
        ?string $role = null,
        ?int $limit = null,
        ?int $offset = null
    ): MembershipInterface {
        // Logic for building context membership for a given registration
    }

    public function buildResourceLinkMembership(
        RegistrationInterface $registration,
        string $resourceLinkIdentifier,
        ?string $role = null,
        ?int $limit = null,
        ?int $offset = null
    ): MembershipInterface {
        // Logic for building resource link membership for a given registration and resource link identifier
    }
};
```

- register the [NRPS library request handler](https://github.com/oat-sa/lib-lti1p3-nrps/blob/master/src/Service/Server/Handler/MembershipServiceServerRequestHandler.php) using your builder in your application services

```yaml
# config/services.yaml
services:
    OAT\Library\Lti1p3Nrps\Service\Server\Handler\MembershipServiceServerRequestHandler:
        arguments:
            - '@App\Nrps\MembershipServiceServerBuilder'
```

- use the [LtiServiceServerHttpFoundationRequestHandlerFactoryInterface](../../Service/Server/Factory/LtiServiceServerHttpFoundationRequestHandlerFactoryInterface.php) service factory to create a controller service

```yaml
# config/services.yaml
services:
    app.nrps_membership_controller:
        class: OAT\Bundle\Lti1p3Bundle\Service\Server\Handler\LtiServiceServerHttpFoundationRequestHandler
        factory: ['@OAT\Bundle\Lti1p3Bundle\Service\Server\Factory\LtiServiceServerHttpFoundationRequestHandlerFactoryInterface', 'create']
        arguments:
            - '@OAT\Library\Lti1p3Nrps\Service\Server\Handler\MembershipServiceServerRequestHandler'
        tags: ['controller.service_arguments']
```

- bind this controller service to a route in your application

```yaml
# config/routes.yaml
nrps_membership:
    path: /platform/service/nrps
    controller: app.nrps_membership_controller
```

- protect this route using the [bundle service firewall](#protecting-platform-service-endpoints)

```yaml
# config/packages/security.yaml
security:
    firewalls:
        secured_service_nrps_area:
            pattern: ^/platform/service/nrps
            stateless: true
            lti1p3_service: { scopes: ['https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'] }
```

- at this point, your application now offers a new endpoint `[GET] /platform/service/nrps`, that automates:
  - HTTP method validation
  - NRPS content type validation
  - access token validation
  - access token NRPS scope validation
  - the response of NRPS memberships representations, relying on the provided membership builder implementation
    
**Note**: exposing a controller as service is convenient but not mandatory, you can still inject the [LtiServiceServerHttpFoundationRequestHandlerFactoryInterface](../../Service/Server/Factory/LtiServiceServerHttpFoundationRequestHandlerFactoryInterface.php) in a controller constructor to have more control on this process, as done in the bundle [TestServiceAction](../../Tests/Resources/Action/Platform/Service/TestServiceAction.php) for example.
