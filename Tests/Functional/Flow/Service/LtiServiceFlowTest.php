<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Tests\Functional\Flow\Service;

use OAT\Bundle\Lti1p3Bundle\Tests\Traits\LoggerTestingTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LtiServiceFlowTest extends WebTestCase
{
    use SecurityTestingTrait;
    use LoggerTestingTrait;

    /** @var KernelBrowser */
    private $client;

    /** @var RegistrationInterface */
    private $registration;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');
    }

    public function testItCanHandleLtiServiceRequest(): void
    {
        $credentials = $this->generateCredentials($this->registration);

        $this->client->request(
            Request::METHOD_GET,
            '/test/service',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)
            ]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode((string)$response->getContent(), true);
        $this->assertEquals($this->registration->getClientId(), current($responseData['claims']['aud']));
        $this->assertEquals($this->registration->getIdentifier(), $responseData['registration']);
        $this->assertEquals($this->registration->getIdentifier(), $responseData['token_registration']);
        $this->assertEquals(['allowed-scope'], $responseData['roles']);
        $this->assertEquals($credentials, $responseData['credentials']);
        $this->assertEquals(
            [
                'Registration found for client_id: client_id',
                'Platform key chain found for registration: testRegistration',
                'JWT access token is valid',
                'JWT access token scopes are valid'
            ],
            $responseData['validations']['successes']
        );
        $this->assertNull($responseData['validations']['error']);

        $this->assertHasLogRecord('test-service service success', LogLevel::INFO);
    }

    public function testItCanHandleLtiServiceRequestWithHandlerAsControllerService(): void
    {
        $credentials = $this->generateCredentials($this->registration);

        $this->client->request(
            Request::METHOD_GET,
            '/test/service-with-handler-as-controller-service',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)
            ]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode((string)$response->getContent(), true);
        $this->assertEquals($this->registration->getClientId(), current($responseData['claims']['aud']));
        $this->assertEquals($this->registration->getIdentifier(), $responseData['registration']);
        $this->assertEquals($this->registration->getIdentifier(), $responseData['token_registration']);
        $this->assertEquals(['allowed-scope'], $responseData['roles']);
        $this->assertEquals($credentials, $responseData['credentials']);
        $this->assertEquals(
            [
                'Registration found for client_id: client_id',
                'Platform key chain found for registration: testRegistration',
                'JWT access token is valid',
                'JWT access token scopes are valid'
            ],
            $responseData['validations']['successes']
        );
        $this->assertNull($responseData['validations']['error']);

        $this->assertHasLogRecord('test-service service success', LogLevel::INFO);
    }

    public function testItReturnsNotAllowedMethodResponseWithInvalidMethod(): void
    {
        $credentials = $this->generateCredentials($this->registration);

        $this->client->request(
            Request::METHOD_POST,
            '/test/service',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials),
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertStringContainsString(
            'Not acceptable request method, accepts: [get]',
            (string)$response->getContent()
        );

        $this->assertHasLogRecord(
            'Not acceptable request method, accepts: [get]',
            LogLevel::ERROR
        );
    }

    public function testItReturnsNotAcceptableResponseWithInvalidContentType(): void
    {
        $credentials = $this->generateCredentials($this->registration);

        $this->client->request(
            Request::METHOD_GET,
            '/test/service',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'invalid',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials),
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $this->assertStringContainsString(
            'Not acceptable request content type, accepts: application/json',
            (string)$response->getContent()
        );

        $this->assertHasLogRecord(
            'Not acceptable request content type, accepts: application/json',
            LogLevel::ERROR
        );
    }

    public function testItReturnsUnauthorizedResponseWithInvalidScopes(): void
    {
        $credentials = $this->generateCredentials($this->registration, ['invalid']);

        $this->client->request(
            Request::METHOD_GET,
            '/test/service',
            [],
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString(
            'JWT access token scopes are invalid',
            (string)$response->getContent()
        );

        $this->assertHasLogRecord(
            'Access token validation error: JWT access token scopes are invalid',
            LogLevel::ERROR
        );
    }

    public function testItReturnsUnauthorizedResponseWithoutBearer(): void
    {
        $this->client->request(Request::METHOD_GET, '/test/service');

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString(
            'A Token was not found in the TokenStorage',
            (string)$response->getContent()
        );

        $this->assertHasLogRecordThatContains(
            'A Token was not found in the TokenStorage',
            LogLevel::ERROR
        );
    }

    public function testItReturnsUnauthorizedResponseWithInvalidBearer(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/test/service',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid']
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString('The JWT string must have two dots', (string)$response->getContent());

        $this->assertHasLogRecordThatContains(
            'The JWT string must have two dots',
            LogLevel::ERROR
        );
    }

    public function testItReturnsInternalServerErrorResponseWithFailingHandler(): void
    {
        $credentials = $this->generateCredentials($this->registration);

        $this->client->request(
            Request::METHOD_GET,
            '/test/service?shouldThrowException=1',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials),
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertStringContainsString(
            'handler generic error',
            (string)$response->getContent()
        );

        $this->assertHasLogRecord(
            'test-service service error: handler generic error',
            LogLevel::ERROR
        );
    }

    private function generateCredentials(RegistrationInterface $registration, array $scopes = ['allowed-scope']): string
    {
        return $this->createTestClientAccessToken($registration, $scopes);
    }
}
