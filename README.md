# <img src="doc/images/logo/logo.png" width="40" height="40"> [TAO](https://www.taotesting.com/) - LTI 1.3 Symfony Bundle

[![Latest Version](https://img.shields.io/github/tag/oat-sa/bundle-lti1p3.svg?style=flat&label=release)](https://github.com/oat-sa/bundle-lti1p3/tags)
[![License GPL2](http://img.shields.io/badge/licence-LGPL%202.1-blue.svg)](http://www.gnu.org/licenses/lgpl-2.1.html)
[![Build Status](https://github.com/oat-sa/bundle-lti1p3/actions/workflows/build.yaml/badge.svg?branch=master)](https://github.com/oat-sa/bundle-lti1p3/actions)
[![Coverage Status](https://coveralls.io/repos/github/oat-sa/bundle-lti1p3/badge.svg?branch=master)](https://coveralls.io/github/oat-sa/bundle-lti1p3?branch=master)
[![Psalm Level Status](https://shepherd.dev/github/oat-sa/bundle-lti1p3/level.svg)](https://shepherd.dev/github/oat-sa/bundle-lti1p3)
[![Packagist Downloads](http://img.shields.io/packagist/dt/oat-sa/bundle-lti1p3.svg)](https://packagist.org/packages/oat-sa/bundle-lti1p3)
[![IMS Certified](https://img.shields.io/badge/IMS-certified-brightgreen)](https://site.imsglobal.org/certifications/open-assessment-technologies-sa/tao-lti-13-devkit)

> [IMS certified](https://site.imsglobal.org/certifications/open-assessment-technologies-sa/tao-lti-13-devkit) [Symfony](https://symfony.com/) bundle for [LTI 1.3](http://www.imsglobal.org/spec/lti/v1p3) implementations, as [platforms and / or as tools](http://www.imsglobal.org/spec/lti/v1p3/#platforms-and-tools).

This bundle automates the usage of the [TAO LTI 1.3 PHP framework libraries](https://oat-sa.github.io/doc-lti1p3/libraries/lib-lti1p3-core/) within your Symfony application.

## Table of Contents

- [TAO LTI 1.3 PHP framework](#tao-lti-13-php-framework)
- [Installation](#installation)
- [Documentation](#documentation)
- [Tests](#tests)

## TAO LTI 1.3 PHP framework

This bundle is part of the [TAO LTI 1.3 PHP framework](https://oat-sa.github.io/doc-lti1p3/).

## Installation

```console
$ composer require oat-sa/bundle-lti1p3
```

The associated [flex recipe](https://github.com/symfony/recipes-contrib/tree/master/oat-sa/bundle-lti1p3) will generate in your application:

 - `config/routes/lti1p3.yaml`: configurable bundle routes (JWKS, OIDC)
 - `config/packages/lti1p3.yaml`: configurable bundle configuration
 - `LTI1P3_SERVICE_ENCRYPTION_KEY`: configurable (.env) variable (signatures security)

## Documentation

You can find below the bundle documentation, presented by topics.

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
