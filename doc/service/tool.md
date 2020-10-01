# LTI Service - Tool

> How to use the bundle to make your application act as a tool in the context of [LTI services](http://www.imsglobal.org/spec/lti/v1p3/#interacting-with-services).

## Using the ServiceClient

In [LTI services](http://www.imsglobal.org/spec/lti/v1p3/#interacting-with-services) context, a tool, in order to call a service platform endpoint, need to be granted following the [client_credentials grant type with assertion](https://www.imsglobal.org/spec/security/v1p0/#using-json-web-tokens-with-oauth-2-0-client-credentials-grant).

The service [ServiceClient](https://github.com/oat-sa/lib-lti1p3-core/blob/master/src/Service/Client/ServiceClient.php) is available form your application container to make authenticated LTI service calls to a platform by following this standard.

To use it, you can inject anywhere you need the the `ServiceClientInterface` and you need to provide on which registered platform you want to make the call.

For example:

```php
<?php

declare(strict_types=1);

namespace App\Action\Tool\Service;

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LtiServiceClientAction
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var ServiceClientInterface */
    private $client;
    
    public function __construct(RegistrationRepositoryInterface $repository, ServiceClientInterface $client)
    {
        $this->repository = $repository;
        $this->client = $client;
    }

    public function __invoke(Request $request): Response
    {
        $registration = $this->repository->find(...);
        
        $serviceResponse = $this->client->request(
            $registration,                    // Will ask the grant on the registration platform oauth2 endpoint, or form cache
            'GET',                            // Service call method
            'http://platform.com/service',    // Service call url
            ['some' => 'options'],            // Options
            ['scope1', 'scope2']              // Scopes
        );

        // Your logic based on the $serviceResponse ...
        
        return new Response(...);
    }
}
```
**Notes**: once the access token is fetched by the `ServiceClient`, it will cache it (into it's configured cache, for given TTL) to avoid asking every time the platform a new token.
