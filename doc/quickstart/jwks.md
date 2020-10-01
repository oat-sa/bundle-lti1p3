# JWKS Endpoint

> How to use the bundle to expose a [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks) to expose security keys for your platforms and tools.

## Configure a JWKS endpoint

A [JWKS endpoint](https://auth0.com/docs/tokens/concepts/jwks) may be used to expose security keys components between platforms and tools.

The bundle provides a ready to use route handled by [JwksAction](../../Action/Jwks/JwksAction.php), automatically added to your application routes via the associated [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3), in file `config/routes/lti1p3.yaml`

**Default route**: `[GET] /lti1p3/.well-known/jwks/{keySetName}.json`

Configuration example:
```yaml
# config/packages/lti1p3.yaml
lti1p3:
    key_chains:
        platformKey1:
            key_set_name: "platformSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/platform/public1.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/platform/private1.key"
            private_key_passphrase: ~
        platformKey2:
            key_set_name: "platformSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/platform/public2.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/platform/private2.key"
            private_key_passphrase: ~
        toolKey1:
            key_set_name: "toolSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/tool/public1.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/tool/private1.key"
            private_key_passphrase: ~
        toolKey2:
            key_set_name: "toolSet"
            public_key: "file://%kernel.project_dir%/config/secrets/dev/tool/public2.key"
            private_key: "file://%kernel.project_dir%/config/secrets/dev/tool/private2.key"
            private_key_passphrase: ~
    ...
    platforms:
        localPlatform:
            name: "Local platform"
            audience: "http://localhost/platform"
            oidc_authentication_url: "http://localhost/lti1p3/oidc/authentication"
            oauth2_access_token_url: "http://localhost/lti1p3/auth/platformKey/token"
        localTool:
            name: "Local tool"
            audience: "http://localhost/tool"
            oidc_initiation_url: "http://localhost/lti1p3/oidc/initiation"
            launch_url: ~
            deep_linking_url: ~
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
            tool_jwks_url: "http://localhost/lti1p3/.well-known/jwks/toolSet.json"
```

**Notes**:
- the dynamic route `[GET] /lti1p3/.well-known/jwks/{keySetName}.json` expects a key set name, like `platformSet` or `toolSet`, to group key chains and expose their JWK as JWKS
- you can then declare in a registration involving your platform `localPlatform` that the `platform_jwks_url` will be `http://localhost/lti1p3/.well-known/jwks/platformSet.json`: it will expose the keys for the set name `platformSet`
- you can then declare in a registration involving your tool `localTool` that the `tool_jwks_url` will be `http://localhost/lti1p3/.well-known/jwks/toolSet.json`: it will expose the keys for the set name `toolSet`
