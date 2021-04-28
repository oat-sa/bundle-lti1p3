# LTI Message - Tool

> How to use the bundle to make your application act as a tool in the context of [LTI messages](http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details).

## Table of contents

- [Generating tool originating LTI messages](#generating-tool-originating-lti-messages)
- [Validating platform originating LTI messages](#validating-platform-originating-lti-messages)

## Generating tool originating LTI messages

In this section, you'll see how to generate tool originating LTI messages for platforms, compliant to [IMS Security specifications](https://www.imsglobal.org/spec/security/v1p0/#tool-originating-messages).

You can use the provided [ToolOriginatingLaunchBuilder](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Message/Launch/Builder/ToolOriginatingLaunchBuilder.php) to build easily tool originating LTI messages.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Tool\Message;

use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyToolAction
{
    /** @var ToolOriginatingLaunchBuilder */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(ToolOriginatingLaunchBuilder $builder, RegistrationRepositoryInterface $repository)
    {
        $this->builder = $builder;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $message = $this->builder->buildToolOriginatingLaunch(
            $this->repository->find('local'),
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            'http://platform.com/return'
        );

        return new Response($message->toHtmlRedirectForm()); // has to be used this way due to the expected form POST platform side
    }
}
```

**Note**: you can find more details about the `ToolOriginatingLaunchBuilder` in the [related documentation](https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/tool-originating-messages.md#1---tool-side-launch-generation)

## Validating platform originating LTI messages

In this section, you'll see how to handle platform originating messages, in compliance to [IMS Security and OIDC specifications](https://www.imsglobal.org/spec/security/v1p0/#platform-originating-messages).

### Provide tool OIDC initiation

In the [OIDC flow](https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request), the tool will be asked to provide a login initiation endpoint.

The [OidcInitiationAction](../../Action/Tool/Message/OidcInitiationAction.php) is automatically added to your application via the associated [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

**Default route**: `[GET,POST] /lti1p3/oidc/initiation`

You then just need to ensure your tool's `oidc_initiation_url` is configured accordingly:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    tools:
        myTool:
            name: "My Tool"
            audience: "http://tool.com"
            oidc_initiation_url: "http://tool.com/lti1p3/oidc/initiation"
            launch_url: "http://tool.com/launch"
            deep_linking_url: ~
```
**Notes**:
- it will generate a `state` as a JWT and a `nonce` that need to be returned by the platform, unaltered, after OIDC authentication
- it expects to validate them on the final launch endpoint after the OIDC flow

### Protecting tool launch endpoint

Considering you have the following tool launch endpoint:

```yaml
#config/routes.yaml
platform_service:
    path: /launch
    controller: App\Action\Tool\LtiLaunchAction
```

To protect your endpoint, this bundle provides the `lti1p3_message_tool` [security firewall](../../Security/Firewall/Message/LtiToolMessageAuthenticationListener.php) to put in front of your routes:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        secured_tool_area:
            pattern: ^/launch
            stateless: true
            lti1p3_message_tool: true
```

You can optionally restrict allowed message types on this firewall:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        secured_tool_area:
            pattern: ^/launch
            stateless: true
            lti1p3_message_tool: { types: ['LtiResourceLinkRequest'] }
```

It will automatically [handle the provided id token and state validations](https://www.imsglobal.org/spec/security/v1p0/#authentication-response-validation), and add a [LtiToolMessageSecurityToken](../../Security/Authentication/Token/Message/LtiToolMessageSecurityToken.php) in the [security token storage](https://symfony.com/doc/current/security.html), that you can use to retrieve your authentication context.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Tool;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class LtiLaunchAction
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        /** @var LtiToolMessageSecurityToken $token */
        $token = $this->security->getToken();

        // Related registration
        $registration = $token->getRegistration();

        // Related LTI message payload
        $payload = $token->getPayload();

        // Related OIDC state
        $state = $token->getState();

        // You can even access validation results
        $validationResults = $token->getValidationResult();

        // Your service endpoint logic ...

        return new Response(...);
    }
}
```

### Customize launch error handling

During the tool launch, errors can happen (invalid token, nonce already taken, etc).

By default, via the [LtiToolMessageExceptionHandler](../../Security/Exception/LtiToolMessageExceptionHandler.php), the bundle detects if the launch provides a [launch presentation claim](http://www.imsglobal.org/spec/lti/v1p3/#launch-presentation-claim), and performs automatically the redirection to the `return_url`, if given (by appending the `lti_errormsg` query parameter containing the actual error message). If no `return_url` is given, it'll bubble up the exception.

This default behaviour can be customised if you provide your own [LtiToolMessageExceptionHandlerInterface](../../Security/Exception/LtiToolMessageExceptionHandlerInterface.php) implementation.

For example, if you need to translate:

```php
<?php

declare(strict_types=1);

namespace App\Security\Exception;

use OAT\Bundle\Lti1p3Bundle\Security\Exception\LtiToolMessageExceptionHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class MyExceptionHandler implements LtiToolMessageExceptionHandlerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function handle(Throwable $exception, Request $request): Response
    {
        $message = $this->translator->trans($exception->getMessage());

        return new RedirectResponse(sprintf('http://platform.com/error?lti_errormsg=%s', $message));
    }
}
```

You then need to activate it in your application services:

```yaml
# config/services.yaml
services:

    OAT\Bundle\Lti1p3Bundle\Security\Exception\LtiToolMessageExceptionHandlerInterface:
        class: App\Security\Exception\MyExceptionHandler
```
