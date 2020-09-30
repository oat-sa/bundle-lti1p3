# LTI 1.3 Symfony Bundle

[![Latest Version](https://img.shields.io/github/tag/oat-sa/bundle-lti1p3.svg?style=flat&label=release)](https://github.com/oat-sa/bundle-lti1p3/tags)
[![License GPL2](http://img.shields.io/badge/licence-LGPL%202.1-blue.svg)](http://www.gnu.org/licenses/lgpl-2.1.html)
[![Build Status](https://travis-ci.org/oat-sa/bundle-lti1p3.svg?branch=master)](https://travis-ci.org/oat-sa/bundle-lti1p3)
[![Coverage Status](https://coveralls.io/repos/github/oat-sa/bundle-lti1p3/badge.svg?branch=master)](https://coveralls.io/github/oat-sa/bundle-lti1p3?branch=master)
[![Packagist Downloads](http://img.shields.io/packagist/dt/oat-sa/bundle-lti1p3.svg)](https://packagist.org/packages/oat-sa/bundle-lti1p3)


> [Symfony](https://symfony.com/) bundle for [LTI 1.3](http://www.imsglobal.org/spec/lti/v1p3) implementations, as platforms and / or as tools.

This bundle automates the usage of the [LTI 1.3 Core library](https://github.com/oat-sa/lib-lti1p3-core) within your Symfony application.

## Table of Contents

- [Installation](#installation)
- [Tutorials](#tutorials)
- [Tests](#tests)

## Installation

```console
$ composer require oat-sa/bundle-lti1p3
```

The associated [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3) will generate in your application:
 - `config/routes/lti1p3.yaml`: configurable file to automatically enable required routes (JWKS, OIDC)
 - `config/packages/lti1p3.yaml`: configurable file to offer a default configuration for your keys, tools, platforms and registrations
 - `LTI1P3_SERVICE_ENCRYPTION_KEY`: configurable (.env) variable required to provide encryption for your signatures.

## Tutorials

You can find below some tutorials, presented by topics.

### Quick start

- how to [configure the bundle](doc/quickstart/configuration.md)
- how to [expose a JWKS endpoint](doc/quickstart/jwks.md)

### Messages interactions

- how to [handle LTI message interactions as a platform](doc/message/platform.md)
- how to [handle LTI message interactions as a tool](doc/message/tool.md)

### Services interactions

- how to [handle LTI service interactions as a platform](doc/service/platform.md)
- how to [handle LTI service interactions as a tool](doc/service/tool.md)

## Tests

To run provided tests:

```console
$ vendor/bin/phpunit
```

**Note**: see [phpunit file](phpunit.xml.dist) for available suites.
