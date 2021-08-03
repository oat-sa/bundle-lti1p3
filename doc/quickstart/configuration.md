# Configuration

> How to provide configuration to allow your application to act as LTI platform, or tool, or both.

## Table of contents

- [Overview](#overview)
- [Configure a keychain](#configure-a-keychain)
- [Configure a platform](#configure-a-platform)
- [Configure a tool](#configure-a-tool)
- [Configure a registration](#configure-a-registration)

## Overview

On installation, the associated [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3) creates the configuration file `config/packages/lti1p3.yaml`, containing:

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
            oidc_authentication_url: "http://localhost/lti1p3/oidc/authentication"
            oauth2_access_token_url: "http://localhost/lti1p3/auth/platformKey/token"
    tools:
        localTool:
            name: "Local tool"
            audience: "http://localhost/tool"
            oidc_initiation_url: "http://localhost/lti1p3/oidc/initiation"
            launch_url: ~
            deep_linking_url: ~
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

In this setup, the bundle allows your application to **act as a platform AND as a tool**.

It contains:

- 2 key chains (`platformKey` and `toolKey`) that can be used for registration, JWKS for example
- 1 platform `localPlatform` with default urls (hostname to adapt)
- 1 tool `localTool` with default urls (hostname to adapt)
- 1 registration `local` that deploys the `localTool` for the `localPlatform` (with client id `client_id`, deployment id `deploymentId1` and using respective `platformKey` and `toolKey` key chains to secure communications)

## Configure a keychain

First you need to [generate a key pair as explained here](https://en.wikibooks.org/wiki/Cryptography/Generate_a_keypair_using_OpenSSL):

```console
$ mkdir -p config/secrets/dev
$ openssl genrsa -out config/secrets/dev/private.key 2048
$ openssl rsa -in config/secrets/dev/private.key -outform PEM -pubout -out config/secrets/dev/public.key
```

Then, add a key chain:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        myKey:
            key_set_name: "myKeySetName"               # [required] key set name
            public_key: "file://path/to/public.key"    # [required] path / content of the public key
            private_key: "file://path/to/private.key"  # [optional] path / content of the private key
            private_key_passphrase: '...'              # [optional] private key passphrase
            algorithm: 'RS256'                         # [optional] keys algorithm (default: RS256)
```
**Notes**:

- optional keys can be omitted
- the unique identifier `myKey` can be used from the [KeyChainRepositoryInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Security/Key/KeyChainRepositoryInterface.php#L27)
- the key set name `myKeySetName` can be used to group key chains together, like by example in the [JwksAction](../../Action/Jwks/JwksAction.php)

## Configure a platform

Platforms (owned or external) can be configured as following:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    platforms:
        myPlatform:
            name: "My Platform"                                                           # [required] platform name
            audience: "http://platform.com"                                               # [required] platform audience
            oidc_authentication_url: "http://platform.com/lti1p3/oidc/authentication"     # [optional] platform OIDC auth url
            oauth2_access_token_url: "http://platform.com/lti1p3/auth/platformKey/token"  # [optional] platform access token url
```
**Notes**:

- optional keys can be omitted
- the unique identifier `myPlatform` can be used into registrations creation (ex: `platform: "myPlatform"`)
- the `audience` will be used in JWT based communications as issuer 
- the `oidc_authentication_url` is automated by the [OidcAuthenticationAction](../../Action/Platform/Message/OidcAuthenticationAction.php)
- the `oauth2_access_token_url`, automated by the [OAuth2AccessTokenCreationAction](../../Action/Platform/Service/OAuth2AccessTokenCreationAction.php), provides the key chain identifier `platformKey` as an uri param to offer an oauth2 server using this key

## Configure a tool

Tools (owned or external) can be configured as following:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    tools:
        myTool:
            name: "My Tool"                                               # [required] tool name
            audience: "http://tool.com"                                   # [required] tool audience
            oidc_initiation_url: "http://tool.com/lti1p3/oidc/initiation" # [required] tool OIDC init url
            launch_url: "http://tool.com/launch"                          # [optional] tool default launch url
            deep_linking_url: ~                                           # [optional] tool DeepLinking url
```
**Notes**:

- optional keys can be omitted
- the unique identifier `myTool` can be used into registrations creation (ex: `tool: "myTool"`)
- the `audience` will be used in JWT based communications as issuer 
- the `oidc_initiation_url` is handled by the [OidcInitiationAction](../../Action/Tool/Message/OidcInitiationAction.php)
- the `launch_url` is used to configure your default tool launch url
- the `deep_linking_url` is used to configure your default tool DeepLinking url (for content selection)

## Configure a registration

To add a registration:

```yaml
# config/packages/lti1p3.yaml
lti1p3:
    registrations:
        myRegistration:
            client_id: "myClientId"                                                            # [required] client id
            platform: "myPlatform"                                                             # [required] platform identifier
            tool: "myTool"                                                                     # [required] tool identifier
            deployment_ids:                                                                    # [required] deployment ids
                - "myDeploymentId1"
                - "myDeploymentId2"
            platform_key_chain: "myPlatformKey"                                                # [optional] platform key chain identifier
            tool_key_chain: "myToolKey"                                                        # [optional] tool key chain identifier
            platform_jwks_url: "http://platform.com/lti1p3/.well-known/jwks/platformSet.json"  # [optional] platform JWKS url
            tool_jwks_url: "http://tool.com/lti1p3/.well-known/jwks/toolSet.json"              # [optional] tool JWKS url
            order: 1                                                                           # [optional] order of the registration
```

**Notes**:

- optional keys can be omitted
- the unique identifier `myRegistration` allows the registration to be fetched from the [RegistrationRepositoryInterface](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Registration/RegistrationRepositoryInterface.php#L27)
- the client id `myClientId` will be used in JWT based communications as client_id
- the defined `myTool` tool will be registered for the defined `myPlatform` platform
- the `myPlatformKey` and `myToolKey` key chains will be used to sign respectively from `myPlatform` and `myTool`
- the JWKS urls are exposed by the [JwksAction](../../Action/Jwks/JwksAction.php) (if you own them)
- the `order` can be used to order registration (integer value), all non ordered registrations will go last, in declaration order
