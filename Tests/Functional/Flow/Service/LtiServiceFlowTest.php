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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Tests\Functional\Flow\Service;

use OAT\Bundle\Lti1p3Bundle\Tests\Traits\SecurityTestingTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LtiServiceFlowTest extends WebTestCase
{
    use SecurityTestingTrait;

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
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode((string)$response->getContent(), true);
        $this->assertEquals($this->registration->getClientId(), $responseData['claims']['aud']);
        $this->assertEquals($this->registration->getIdentifier(), $responseData['registration']);
        $this->assertEquals(['allowed-scope'], $responseData['roles']);
        $this->assertEquals($credentials, $responseData['credentials']);
        $this->assertEquals(
            [
                'JWT access token is not expired',
                'Registration found for client_id: client_id',
                'Platform key chain found for registration: testRegistration',
                'JWT access token signature is valid',
                'JWT access token scopes are valid'
            ],
            $responseData['validations']['successes']
        );
        $this->assertNull($responseData['validations']['error']);
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
    }

    private function generateCredentials(RegistrationInterface $registration, array $scopes = ['allowed-scope']): string
    {
        return $this->createTestClientAccessToken($registration, $scopes);
    }
}
