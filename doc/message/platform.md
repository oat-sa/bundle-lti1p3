# LTI Message - Platform

> How to use the bundle to make your application act as a platform in the context of [LTI messages](http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details).

## Table of contents

- [Generating an LTI Launch request](#generating-an-lti-launch-request)
- [Generating an LTI Launch request with OIDC](#generating-an-lti-launch-request-with-oidc)
- [Handling OIDC authentication](#handling-oidc-authentication)

## Generating an LTI Launch request 

You can use the provided service [LtiLaunchRequestBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Launch/Builder/LtiLaunchRequestBuilder.php) to build easily LTI launch requests.

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

**Note**: you can find more details about the `LtiLaunchRequestBuilder` in the [related documentation](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/resource-link-launch.md)

## Generating an LTI Launch request with OIDC

You can use the provided service [OidcLaunchRequestBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Launch/Builder/OidcLaunchRequestBuilder.php) to build easily OIDC LTI launch requests.

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

## Handling OIDC authentication

In the case an LTI launch request was started with the [OIDC flow](https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request), the platform will be asked to provide (or delegate) authentication for a login hint.

The [OidcLoginAuthenticationAction](../../Action/Platform/Message/OidcAuthenticationAction.php) is automatically added to your application via the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

**Default route**: `[GET,POST] /lti1p3/oidc/login-authentication`

To personalise how the actual authentication will be handled, you need to provide a [UserAuthenticatorInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Security/User/UserAuthenticatorInterface.php) implementation, as explained [here](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/quickstart/interfaces.md#mandatory-interfaces).

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
        // Your authentication logic ...

        return new UserAuthenticationResult(
            true,
            $loginHint !== 'anonymous' ? new UserIdentity($loginHint, ...) : null
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
