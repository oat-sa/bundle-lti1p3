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

## Tests

To run provided tests:

```console
$ vendor/bin/phpunit
```

**Note**: see [phpunit file](phpunit.xml.dist) for available suites.
