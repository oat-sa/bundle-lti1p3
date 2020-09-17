# LTI Message - Tool

> How to use the bundle to make your application act as a tool in the context of [LTI messages](http://www.imsglobal.org/spec/lti/v1p3/#lti-message-general-details).

## Table of contents

- [Handling OIDC login initiation](#handling-oidc-login-initiation)
- [Protecting tool launch endpoint](#protecting-tool-launch-endpoint)

## Handling OIDC login initiation

In the case an LTI launch request was started with the [OIDC flow](https://www.imsglobal.org/spec/security/v1p0/#step-2-authentication-request), the tool will be asked to provide a login initiation endpoint.

The [OidcLoginInitiationAction](../../Action/Tool/Message/OidcInitiationAction.php) is automatically added to your application via the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`.

**Default route**: `[GET,POST] /lti1p3/oidc/login-initiation`

You then just need to ensure your tool's `oidc_login_initiation_url` is configured accordingly:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    tools:
        myTool:
            name: "My Tool"
            audience: "http://example.com/tool"
            oidc_login_initiation_url: "http://example.com/lti1p3/oidc/login-initiation"
            launch_url: "http://example.com/tool/launch"
            deep_link_launch_url: ~
```
**Notes**:
- it will generate a `state` as a JWT
- it expects to validate it on the actual launch endpoint if the LTI launch request used OIDC flow

## Protecting tool launch endpoint

Considering you have the following tool launch endpoint:

```yaml
#config/routes.yaml
platform_service:
    path: /tool/launch
    controller: App\Action\Tool\LtiLaunchAction
```

To protect your endpoint, this bundle provides the `lti1p3_message` [security firewall](../../Security/Firewall/Message/LtiMessageAuthenticationListener.php) to put in front of your routes:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        lti1p3_message:
            pattern: ^/tool/launch
            stateless: true
            lti1p3_message: true
```

It will automatically handle the provided id token authentication (and state in case of OIDC), and add a [LtiMessageToken](../../Security/Authentication/Token/Message/LtiMessageSecurityToken.php) in the [security token storage](https://symfony.com/doc/current/security.html), that you can use to retrieve your authentication context.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Tool;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiMessageToken;
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
        /** @var LtiMessageToken $token */
        $token = $this->security->getToken();

        // Related registration
        $registration = $token->getRegistration();

        // Related LTI message
        $ltiMessage = $token->getLtiMessage();

        // You can even access validation results
        $validationResults = $token->getValidationResult();

        // Your service endpoint logic ...

        return new Response(...);
    }
}
```
