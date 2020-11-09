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

use Lcobucci\JWT\Parser;
use OAT\Bundle\Lti1p3Bundle\Tests\Traits\SecurityTestingTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuth2AccessTokenCreationActionTest extends WebTestCase
{
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

        $token = (new Parser())->parse($responseData['access_token']);

        $this->assertEquals($this->registration->getClientId(), $token->getClaim('aud'));
        $this->assertEquals(['allowed-scope'], $token->getClaim('scopes'));
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
    }

    public function testWithoutGrantType(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            sprintf('/lti1p3/auth/%s/token', $this->keyChain->getIdentifier())
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
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

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
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
