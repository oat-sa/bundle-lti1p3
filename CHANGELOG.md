CHANGELOG
=========

6.2.0
-----

* Extended psr/cache dependency versions

6.1.0
-----

* Added possibility to order registrations
* Added possibility to omit non required fields in yaml bundle configuration  
* Updated documentation

6.0.0
-----

* Added github actions CI
* Added LTI service layer automation to ease LTI libraries usage through the bundle
* Added possibility to restrict LTI message types on message firewalls
* Removed jenkins and travis CI
* Updated oat-sa/lib-lti1p3-core dependency to version 6.0
* Updated documentation

5.0.0
-----

* Added psalm support
* Updated oat-sa/lib-lti1p3-core dependency to version 5.0
* Updated documentation

4.0.0
-----

* Added PHP 8 support (and kept >=7.2)
* Added possibility to use keys with algorithms RS256/384/512, HS256/384/512 or ES256/384/512
* Updated oat-sa/lib-lti1p3-core dependency to version 4.0
* Updated RegistrationRepository to use oat-sa/lib-lti1p3-core collections  
* Updated documentation

3.4.0
-----

* Added possibility to customise tools launch error handling

3.3.1
-----

* Fixed security factories for Symfony 4.4 compatibility

3.3.0
-----

* Added possibility to generate test access token to ease LTI services testing

3.2.0
-----

* Added automatic logging from platform and tool security endpoints (OIDC, OAuth2)

3.1.0
-----

* Added possibility to configure handled scopes for service access token generator
* Added possibility to configure allowed scopes per service firewall
* Updated oat-sa/lib-lti1p3-core dependency to version 3.2.0
* Updated documentation

3.0.0
-----

* Added Travis integration
* Upgraded for oat-sa/lib-lti1p3-core version 3.0.0
* Reworked platform and tool message security layers
* Updated php dependency to >= 7.2.0
* Updated documentation

2.1.0
-----

* Added LTI messages error handling delegation

2.0.0
-----

* Updated licence to LGPL

1.0.0
-----

* Provided LTI messages handling (optionally with OIDC) as platform and tool
* Provided LTI services handling as platform and tool