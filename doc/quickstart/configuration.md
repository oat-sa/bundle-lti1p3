# Configuration

> How to provide configuration to be able to use the library as an LTI tool, or a platform, or both.

## Table of contents

- [Overview](#overview)
- [Configure a keychain](#configure-a-keychain)
- [Configure a platform](#configure-a-platform)
- [Configure a tool](#configure-a-tool)
- [Configure a registration](#configure-a-registration)

## Overview

On installation, thanks to the related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), the configuration file will be created in `config/packages/lti1p3.yaml`.

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        platformKey:
            key_set_name: "platformSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/public.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/private.key"
            private_key_passphrase: ~
        toolKey:
            key_set_name: "toolSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/public.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/private.key"
            private_key_passphrase: ~
    platforms:
        localPlatform:
            name: "Local platform"
            audience: "http://localhost/platform"
            oidc_authentication_url: "http://localhost/lti1p3/oidc/login-authentication"
            oauth2_access_token_url: "http://localhost/lti1p3/auth/platformKey/token"
    tools:
        localTool:
            name: "Local tool"
            audience: "http://localhost/tool"
            oidc_login_initiation_url: "http://localhost/lti1p3/oidc/login-initiation"
            launch_url: ~
            deep_link_launch_url: ~
    registrations:
        local:
            client_id: "client_id"
            platform: "localPlatform"
            tool: "localTool"
            deployment_ids:
                - "deploymentId1"
            platform_key_chain: "platformKey"
            tool_key_chain: "toolKey"
            platform_jwks_url: ~
            tool_jwks_url: ~
```

In this setup, the bundle allows your application to **act as a platform and as a tool**.

It contains:
- 2 key chains (`platformKey` and `toolKey`) that can be used for registration, JWKS for example
- 1 platform `localPlatform` with default urls (hostname to adapt)
- 1 tool `localTool` with default urls (hostname to adapt)
- 1 registration `local` that deploys the `localTool` for the `localPlatform` (with client id `client_id`, deployment id `deploymentId1` and using respective `platformKey` and `toolKey` key chains to secure communications)

## Configure a keychain

First you need to [generate a key pair as explained here](https://en.wikibooks.org/wiki/Cryptography/Generate_a_keypair_using_OpenSSL).

Then, add a key chain:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        myKey:
            key_set_name: "myKeySetName"
            public_key: "file://path/to/public.key"
            private_key: "file://path/to/private.key"
            private_key_passphrase: 'someSecretPassPhrase'
```
**Notes**:
- the unique identifier `myKey` can be used from the [KeyChainRepositoryInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Security/Key/KeyChainRepositoryInterface.php#L27)
- the key set name `myKeySetName` can be used to group key chains together, like by example in the [JwksAction](../../Action/Jwks/JwksAction.php)

## Configure a platform

Platforms (owned or external) can be configured as following:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    platforms:
        myPlatform:
            name: "My Platform"
            audience: "http://example.com/platform"
            oidc_authentication_url: "http://example.com/lti1p3/oidc/login-authentication"
            oauth2_access_token_url: "http://example.com/lti1p3/auth/platformKey/token"
```
**Notes**:
- the unique identifier `myPlatform` can be used into registrations creation (ex: `platform: "myPlatform"`)
- the `audience` will be used in JWT based communications as issuer 
- the `oidc_authentication_url` is automated by the [OidcLoginAuthenticationAction](../../Action/Platform/Message/OidcAuthenticationAction.php)
- the `oauth2_access_token_url`, automated by the [OAuth2AccessTokenCreationAction](../../Action/Platform/Service/OAuth2AccessTokenCreationAction.php), provides the key chain identifier `platformKey` as an uri param to offer an oauth2 server using this key

## Configure a tool

Tools (owned or external) can be configured as following:

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
- the unique identifier `myTool` can be used into registrations creation (ex: `tool: "myTool"`)
- the `audience` will be used in JWT based communications as issuer 
- the `oidc_login_initiation_url` is handled by the [OidcLoginInitiationAction](../../Action/Tool/Message/OidcInitiationAction.php)
- the `launch_url` is used to configure your default tool launch url
- the `deep_link_launch_url` is used to configure your default tool deep links url

## Configure a registration

To add a registration:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    registrations:
        myRegistration:
            client_id: "myClientId"
            platform: "myPlatform"
            tool: "myTool"
            deployment_ids:
                - "myDeploymentId1"
                - "myDeploymentId2"
            platform_key_chain: "myPlatformKey"
            tool_key_chain: "myToolKey"
            platform_jwks_url: "http://example.com/lti1p3/.well-known/jwks/platformSet.json"
            tool_jwks_url: "http://example.com/lti1p3/.well-known/jwks/toolSet.json"
```
**Notes**:
- the unique identifier `myRegistration` allows the registration to be fetched from the [RegistrationRepositoryInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Registration/RegistrationRepositoryInterface.php#L27)
- the client id `myClientId` will be used in JWT based communications as client id
- the defined `myTool` tool will be registered for the defined `myPlatform` platform
- the `myPlatformKey` and `myToolKey` key chains will be used to sign respectively from `myPlatform` and `myTool`
- the JWKS urls are handled by [JwksAction](../../Action/Jwks/JwksAction.php)
