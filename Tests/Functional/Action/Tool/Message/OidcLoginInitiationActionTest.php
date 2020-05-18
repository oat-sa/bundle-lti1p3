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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Functional\Action\Tool\Message;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OidcLoginInitiationActionTest extends WebTestCase
{
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

    public function testValidLoginInitiationWithPostMethod(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/lti1p3/oidc/login-initiation',
            [
                'iss' => $this->registration->getPlatform()->getAudience(),
                'login_hint' => 'login_hint',
                'target_link_uri'  => 'target_link_uri',
                'client_id' => 'client_id',
                'lti_deployment_id' => 'deploymentId1',
            ]
        );

        $this->assertLoginInitiationResponse($this->client->getResponse());
    }

    public function testValidLoginInitiationWithGetMethod(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            sprintf(
                '/lti1p3/oidc/login-initiation?%s',
                http_build_query(
                    [
                        'iss' => $this->registration->getPlatform()->getAudience(),
                        'login_hint' => 'login_hint',
                        'target_link_uri'  => 'target_link_uri',
                        'client_id' => 'client_id',
                        'lti_deployment_id' => 'deploymentId1',
                    ]
                )
            )
        );

        $this->assertLoginInitiationResponse($this->client->getResponse());
    }

    public function testLoginInitiationWithInvalidClientId(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/lti1p3/oidc/login-initiation',
            [
                'iss' => $this->registration->getPlatform()->getAudience(),
                'login_hint' => 'login_hint',
                'target_link_uri'  => 'target_link_uri',
                'client_id' => 'invalid',
                'lti_deployment_id' => 'deploymentId1',
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Cannot find registration for OIDC request', (string)$response->getContent());
    }

    public function testLoginInitiationWithInvalidDeploymentId(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/lti1p3/oidc/login-initiation',
            [
                'iss' => $this->registration->getPlatform()->getAudience(),
                'login_hint' => 'login_hint',
                'target_link_uri'  => 'target_link_uri',
                'client_id' => 'client_id',
                'lti_deployment_id' => 'invalid',
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Cannot find deployment for OIDC request', (string)$response->getContent());
    }

    private function assertLoginInitiationResponse(Response $response): void
    {
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

        $query = parse_url($this->client->followRedirect()->getUri(), PHP_URL_QUERY);
        parse_str($query, $queryParameters);
        $this->assertEquals('target_link_uri', $queryParameters['redirect_uri']);
        $this->assertEquals('client_id', $queryParameters['client_id']);
        $this->assertEquals('login_hint', $queryParameters['login_hint']);
        $this->assertArrayHasKey('nonce', $queryParameters);
        $this->assertArrayHasKey('state', $queryParameters);
    }
}
