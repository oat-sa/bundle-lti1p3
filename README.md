# LTI 1.3 Bundle

> [Symfony](https://symfony.com/) bundle for LTI 1.3 automation, based on [lti1p3-core library](https://github.com/oat-sa/lib-lti1p3-core)

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Tests](#tests)

## Installation

```console
$ composer require oat-sa/bundle-lti1p3
```

## Usage

### Endpoints

This bundle provides by default the following endpoints:

| Endpoint                                    | Description                         |
|---------------------------------------------|-------------------------------------|
| `[GET] /.well-known/jwks/{keySetName}.json` | JWKS Endpoint                       |
| `[GET, POST] /oidc/login-initiation`        | Tool OIDC login initiation endpoint |

### Configuration

This bundle expects a configuration file in `config/packages/lti1p3.yaml` as following:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        kid1:
            key_set_name: "platformSet"
            public_key: "file://%kernel.project_dir%/config/keys/public.key"
            private_key: "file://%kernel.project_dir%/config/keys/private.key"
            private_key_passphrase: ~
        kid2:
            key_set_name: "toolSet"
            public_key: "file://%kernel.project_dir%/config/keys/public.key"
            private_key: "file://%kernel.project_dir%/config/keys/private.key"
            private_key_passphrase: ~
    platforms:
        testPlatform:
            name: "Test platform"
            audience: "http://localhost:8000/platform"
            oidc_authentication_url: "http://localhost:8000/platform/oidc-auth"
            oauth2_access_token_url: "http://localhost:8000/platform/access-token"
    tools:
        testTool:
            name: "Test tool"
            audience: "http://localhost:8000/tool"
            oidc_login_initiation_url: "http://localhost:8000/tool/oidc-init"
            launch_url: "http://localhost:8000/tool/launch"
            deep_link_launch_url: "http://localhost:8000/tool/deep-launch"
    registrations:
        testRegistration:
            client_id: "client_id"
            platform: "testPlatform"
            tool: "testTool"
            deployment_ids:
                - "deploymentId1"
                - "deploymentId2"
            platform_key_chain: "kid1"
            tool_key_chain: "kid2"
            platform_jwks_url: ~
            tool_jwks_url: ~
```
**Notes**:
- we define here two key chains with identifiers `kid1` and `kid2`
- we define here one platform with identifier `testPlatform`
- we define here one tool with identifier `testTool`
- we define here one registration with identifier `testRegistration` that deploys
    - the tool `testTool` using the key chain `kid2`
    - for the platform `testPlatform` using the key chain `kid1`
    - on the deployments ids `deploymentId1` and `deploymentId2`
    - using the oauth2 client id `client_id`

### Security

You can, as a tool, automate the protection of any endpoint behind a built in LTI 1.3 firewall.

Considering you have this route definition:
```yaml
# config/routes.yaml
launch:
    path: /tool/launch
    controller: App\Controller\LaunchController
```

You can protect it in your application security configuration by putting it behind the `lti1p3_message` firewall:
```yaml
security:
    firewalls:
        lti1p3_message:
            pattern:   ^/tool/launch
            stateless: true
            lti1p3_message: true
```

And finally, from your controller, if the LTI launch was performed with success, you can access the token:
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\LtiLaunchRequestToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class LaunchController
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(): Response
    {
        /** @var LtiLaunchRequestToken $token */
        $token = $this->security->getToken();

        $message = $token->getLtiMessage();       // to get LTI launch message [LtiMessageInterface]
        $user = $token->getUser();                // to get LTI launch user identifier
        $roles = $token->getRoleNames();          // to get LTI launch roles
        $results = $token->getValidationResult(); // to get LTI launch validation result details


        // ... your logic here
    }
}
```

## Tests

To run provided tests:

```console
$ vendor/bin/phpunit
```

**Note**: see [phpunit file](phpunit.xml.dist) for available suites.
