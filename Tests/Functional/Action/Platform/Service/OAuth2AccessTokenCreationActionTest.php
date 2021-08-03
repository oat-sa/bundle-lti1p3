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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Functional\Action\Platform\Service;

use OAT\Bundle\Lti1p3Bundle\Tests\Traits\LoggerTestingTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Grant\ClientAssertionCredentialsGrant;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuth2AccessTokenCreationActionTest extends WebTestCase
{
    use LoggerTestingTrait;
    use SecurityTestingTrait;

    /** @var KernelBrowser */
    private $client;

    /** @var RegistrationInterface */
    private $registration;

    /** @var KeyChainInterface */
    private $keyChain;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->resetTestLogger();

        $this->registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');

        $this->keyChain = static::$container
            ->get(KeyChainRepositoryInterface::class)
            ->find('kid1');
    }

    public function testWithValidClientAssertion(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            sprintf('/lti1p3/auth/%s/token', $this->keyChain->getIdentifier()),
            $this->generateCredentials($this->registration, ['allowed-scope'])
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode((string)$response->getContent(), true);

        $token = $this->parseJwt($responseData['access_token']);

        $this->assertEquals($this->registration->getClientId(), current($token->getClaims()->get('aud')));
        $this->assertEquals(['allowed-scope'], $token->getClaims()->get('scopes'));

        $this->assertHasLogRecord('OAuth2AccessTokenCreationAction: access token generation success', LogLevel::INFO);
    }

    public function testWithInvalidScopes(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            sprintf('/lti1p3/auth/%s/token', $this->keyChain->getIdentifier()),
            $this->generateCredentials($this->registration, ['invalid'])
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $this->assertHasLogRecord(
            'OAuth2AccessTokenCreationAction: The requested scope is invalid, unknown, or malformed',
            LogLevel::ERROR
        );
    }

    public function testWithoutGrantType(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            sprintf('/lti1p3/auth/%s/token', $this->keyChain->getIdentifier())
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->assertHasLogRecord(
            'OAuth2AccessTokenCreationAction: The authorization grant type is not supported by the authorization server.',
            LogLevel::ERROR
        );
    }

    public function testWithInValidClientAssertion(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            sprintf('/lti1p3/auth/%s/token', $this->keyChain->getIdentifier()),
            [
                'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
                'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                'client_assertion' => 'invalid',
                'scope' => ''
            ]
        );

        if (PHP_VERSION_ID < 70300) {
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        } else {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        }

        $this->assertHasLogRecord(
            'OAuth2AccessTokenCreationAction: The user credentials were incorrect.',
            LogLevel::ERROR
        );
    }

    public function testWithInValidKeyChainIdentifier(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            sprintf('/lti1p3/auth/%s/token', 'invalid'),
            [
                'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
                'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                'client_assertion' => 'invalid',
                'scope' => ''
            ]
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->assertHasLogRecord(
            'OAuth2AccessTokenCreationAction: Invalid key chain identifier',
            LogLevel::ERROR
        );
    }

    private function generateCredentials(RegistrationInterface $registration, array $scopes = []): array
    {
        return [
            'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
            'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
            'client_assertion' => $this->createTestClientAssertion($registration),
            'scope' => implode(' ', $scopes)
        ];
    }
}
