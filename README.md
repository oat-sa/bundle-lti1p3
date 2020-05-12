# LTI 1.3 Bundle

> [Symfony](https://symfony.com/) bundle for LTI 1.3 automation, based on [lti1p3-core library](https://github.com/oat-sa/lib-lti1p3-core)

## Table of Contents

- [Installation](#installation)
- [Tutorials](#tutorials)
- [Tests](#tests)

## Installation

```console
$ composer require oat-sa/bundle-lti1p3
```

The related [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3) will generate:
 - the configurable `config/routes/lti1p3.yaml` file to automatically enable required routes
 - the configurable `config/packages/lti1p3.yaml` file to offer a default configuration for your keys, tools, platforms and registrations
 - the configurable `LTI1P3_SERVICE_ENCRYPTION_KEY` env variable required to provide encryption of your signatures.

## Tutorials

You can find below some tutorials, presented by topics.

### Quick start

- how to [configure the bundle](doc/quickstart/configuration.md)
- how to expose a [JWKS endpoint](doc/quickstart/jwks.md)

### Messages interactions

- how to handle a [LTI message interactions as a platform](doc/message/platform.md)
- how to handle a [LTI message interactions as a tool](doc/message/tool.md)

### Services interactions

- how to handle a [LTI service interactions as a platform](doc/service/platform.md)
- how to handle a [LTI service interactions as a tool](doc/service/tool.md)

## Tests

To run provided tests:

```console
$ vendor/bin/phpunit
```

**Note**: see [phpunit file](phpunit.xml.dist) for available suites.
