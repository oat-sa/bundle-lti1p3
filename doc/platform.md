# Platform

> How to use the bundle to make your application act as an platform.

## Table of contents

- [LTI Message](#lti-message)

## LTI Message

Documentation about how this bundle can offer [LTI messages](http://www.imsglobal.org/spec/lti/v1p3#lti-launch-0) support to your application, as a platform.

### Configure a JWKS endpoint

A [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks) is needed to avoid key exchanges between tools and platforms.

The bundle provides a ready to use route handled by [JwksAction](../Action/Jwks/JwksAction.php), automatically added to your application routes via the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`

For example:
```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        platformKey:
            key_set_name: "platformSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/public.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/private.key"
            private_key_passphrase: ~
    ...
    platforms:
        localPlatform:
            name: "Local platform"
            audience: "http://localhost/platform"
            oidc_authentication_url: "http://localhost/lti1p3/oidc/login-authentication"
            oauth2_access_token_url: "http://localhost/lti1p3/auth/platformKey/token"
    ...
    registrations:
        local:
            client_id: "client_id"
            platform: "localPlatform"
            tool: "localTool"
            deployment_ids:
                - "deploymentId1"
            platform_key_chain: "platformKey"
            tool_key_chain: "toolKey"
            platform_jwks_url: "http://localhost/lti1p3/.well-known/jwks/platformSet.json"
            tool_jwks_url: ~
```

**Notes**:
- the dynamic route `'/lti1p3/.well-known/jwks/{keySetName}.json'` expects a key set name, like `platformSet`, to group key chains and expose their JWK as JWKS
- you can then declare in a registration involving your platform `localPlatform` that the `platform_jwks_url` will be `http://localhost/lti1p3/.well-known/jwks/platformSet.json`: it will expose the keys for the set name `platformSet`

### Generating an LTI Launch request 

You can use the provided service [LtiLaunchRequestBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Launch/Builder/LtiLaunchRequestBuilder.php) to build easily launch requests.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyPlatformAction
{
    /** @var LtiLaunchRequestBuilder */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(LtiLaunchRequestBuilder $builder, RegistrationRepositoryInterface $repository)
    {
        $this->builder = $builder;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $ltiLaunchRequest = $this->builder->buildResourceLinkLtiLaunchRequest(
            new ResourceLink('identifier'),
            $this->repository->find('local')
        );

        return new Response($ltiLaunchRequest->toHtmlLink('click me'));
    }
}
```

You can find more details about the `LtiLaunchRequestBuilder` in the [related documentation](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/resource-link-launch.md)

### Generating an LTI Launch request with OIDC

You can use the provided service [OidcLaunchRequestBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Launch/Builder/OidcLaunchRequestBuilder.php) to build easily OIDC launch requests.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyPlatformAction
{
    /** @var OidcLaunchRequestBuilder */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(OidcLaunchRequestBuilder $builder, RegistrationRepositoryInterface $repository)
    {
        $this->builder = $builder;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $oidcLtiLaunchRequest = $this->builder->buildResourceLinkOidcLaunchRequest(
            new ResourceLink('identifier'),
            $this->repository->find('local'),
            'loginHint'
        );

        return new Response($oidcLtiLaunchRequest->toHtmlLink('click me'));
    }
}
```

You can find more details about the `OidcLaunchRequestBuilder` in the [related documentation](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/oidc-resource-link-launch.md)

### Handling an OIDC authentication

In the [OIDC flow](https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request), a platform is asked to provide (or delegate) authentication for a login hint.

The [OidcLoginAuthenticationAction](../Action/Platform/Message/OidcLoginAuthenticationAction.php) is automatically added to your application via the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

To configure how the actual authentication is handled, you need to provide a [UserAuthenticatorInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Security/User/UserAuthenticatorInterface.php) implementation, as explained [here](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/quickstart/interfaces.md#mandatory-interfaces).

For example:

```php
<?php

namespace App\Security\User;

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

class UserAuthenticator implements UserAuthenticatorInterface
{
    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        return new UserAuthenticationResult(
            true,
            $loginHint !== 'anonymous' ? new UserIdentity($loginHint) : null
        );
    }
}
```

You then need to activate it in your application services as following:

```yaml
# config/services.yaml
services:
    OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface:
        class: App\Security\User\UserAuthenticator
```

## LTI Service

Documentation about how this bundle can offer [LTI service](http://www.imsglobal.org/spec/lti/v1p3/#interacting-with-services) support to your application, as a platform.

### OAuth2 server

The [OAuth2AccessTokenCreationAction](../Action/Platform/Service/OAuth2AccessTokenCreationAction.php) is automatically added to your application via the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

This endpoint allows tools to get granted to request your platform services endpoints, by following the [client_credentials grant type with assertion](https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant). 
