# LTI Message - Platform

> How to use the bundle to make your application act as a platform in the context of [LTI messages](http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details).

## Table of contents

- [Generating platform originating LTI messages](#generating-platform-originating-lti-messages)
- [Validating tool originating LTI messages](#validating-tool-originating-lti-messages)

## Generating platform originating LTI messages

In this section, you'll see how to generate platform originating LTI messages for tools, compliant to [IMS Security and OIDC specifications](https://www.imsglobal.org/spec/security/v1p0/#platform-originating-messages).

### Generic messages

You can use the provided [PlatformOriginatingLaunchBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Message/Launch/Builder/PlatformOriginatingLaunchBuilder.php) to build easily platform originating LTI messages.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyPlatformAction
{
    /** @var PlatformOriginatingLaunchBuilder */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(PlatformOriginatingLaunchBuilder $builder, RegistrationRepositoryInterface $repository)
    {
        $this->builder = $builder;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $message = $this->builder->buildPlatformOriginatingLaunch(
            $this->repository->find('local'),
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'http://tool.com/launch',
            'loginHint'
        );

        return new Response($message->toHtmlLink('launch'));
    }
}
```

**Note**: you can find more details about the `PlatformOriginatingLaunchBuilder` in the [related documentation](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/platform-originating-messages.md#1---platform-side-launch-generation)

### LTI Resource Link launch request messages

This bundle also allow you to perform easily launches of an [LTI Resource Link](http://www.imsglobal.org/spec/lti/v1p3/#resource-link-launch-request-message).

This becomes handy when a platform owns an LTI Resource Link to a tool resource (previously fetched with [DeepLinking](https://www.imsglobal.org/spec/lti-dl/v2p0) for example).

First of all, you need to create or retrieve an [LtiResourceLink](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Resource/LtiResourceLink/LtiResourceLink.php) instance:

```php
<?php

use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;

$ltiResourceLink = new LtiResourceLink(
    'resourceLinkIdentifier',
    [
        'url' => 'http://tool.com/resource',
        'title' => 'Some title'
    ]
);
```

Once your `LtiResourceLinkInterface` implementation is ready, you can use the [LtiResourceLinkLaunchRequestBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Message/Launch/Builder/LtiResourceLinkLaunchRequestBuilder.php) to create an LTI Resource Link launch message:

```php
<?php

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyPlatformAction
{
    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(LtiResourceLinkLaunchRequestBuilder $builder, RegistrationRepositoryInterface $repository)
    {
        $this->builder = $builder;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $ltiResourceLink = new LtiResourceLink(
            'resourceLinkIdentifier',
            [
                'url' => 'http://tool.com/resource',
                'title' => 'Some title'
            ]
        );

        $message = $this->builder->buildLtiResourceLinkLaunchRequest(
            $ltiResourceLink,
            $this->repository->find('local'),
            'loginHint'
        );

        return new Response($message->toHtmlLink('launch LTI Resource Link'));
    }
}
```

**Note**: you can find more details about the `LtiResourceLinkLaunchRequestBuilder` in the [related documentation](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/platform-originating-messages.md#1---platform-side-launch-generation)

### Provide platform OIDC authentication

During the [OIDC flow](https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request), the platform will be asked to provide (or delegate) authentication for a login hint.

The [OidcAuthenticationAction](../../Action/Platform/Message/OidcAuthenticationAction.php) is automatically added to your application via the associated [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

**Default route**: `[GET,POST] /lti1p3/oidc/authentication`

You then just need to ensure your platform's `oidc_authentication_url` is configured accordingly:

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

To personalise how the actual authentication will be handled, you need to provide a [UserAuthenticatorInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Security/User/UserAuthenticatorInterface.php) implementation, as explained [here](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/quickstart/interfaces.md#mandatory-interfaces).

For example:

```php
<?php

namespace App\Security\User;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

class UserAuthenticator implements UserAuthenticatorInterface
{
    public function authenticate(RegistrationInterface $registration, string $loginHint): UserAuthenticationResultInterface
    {
        // Perform user authentication based on the registration and login hint
        // (ex: owned session, LDAP, external auth service, etc)
        ...       

        return new UserAuthenticationResult(
           true,                                          // success
           new UserIdentity('userIdentifier', 'userName') // authenticated user identity
       ); 
    }
}
```

You then need to activate it in your application services:

```yaml
# config/services.yaml
services:

    OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface:
        class: App\Security\User\UserAuthenticator
```

## Validating tool originating LTI messages

Plaforms can also receive LTI messages from tools (see [DeepLinking response](https://www.imsglobal.org/spec/lti-dl/v2p0/#deep-linking-response-example) for example).

This bundle offers you a way to protect your platform application endpoints that will receive tool originating messages.

Considering you have the following platform endpoint:

```yaml
#config/routes.yaml
platform_return:
    path: /platform/return
    controller: App\Action\Platform\ReturnAction
```

To protect your endpoint, this bundle provides the `lti1p3_message_platform` [security firewall](../../Security/Firewall/Message/LtiPlatformMessageAuthenticationListener.php) to put in front of your routes:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        secured_platform_area:
            pattern: ^/platform/return
            stateless: true
            lti1p3_message_platform: true
```

It will automatically [handle the provided JWT parameter validation](https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation-0), and add a [LtiPlatformMessageSecurityToken](../../Security/Authentication/Token/Message/LtiPlatformMessageSecurityToken.php) in the [security token storage](https://symfony.com/doc/current/security.html), that you can use to retrieve your authentication context.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Platform;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiPlatformMessageSecurityToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class ReturnAction
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        /** @var LtiPlatformMessageSecurityToken $token */
        $token = $this->security->getToken();

        // Related registration
        $registration = $token->getRegistration();

        // Related LTI message payload
        $payload = $token->getPayload();

        // You can even access validation results
        $validationResults = $token->getValidationResult();

        // Your service endpoint logic ...

        return new Response(...);
    }
}
```
