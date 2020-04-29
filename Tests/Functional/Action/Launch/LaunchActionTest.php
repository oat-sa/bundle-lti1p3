<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Tests\Functional\Action\Launch;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Message\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LaunchActionTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        if (Carbon::hasTestNow()) {
            Carbon::setTestNow();
        }

        parent::tearDown();
    }

    public function testItCanHandleAnonymousLtiLaunchRequest(): void
    {
        $builder = static::$container->get(LtiLaunchRequestBuilder::class);

        $registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');

        $launchRequest = $builder->buildResourceLinkLtiLaunchRequest(
            new ResourceLink('resourceLinkIdentifier'),
            $registration,
            null,
            [
                'roles'
            ],
            [
                new ContextClaim('contextId'),
                'custom' => 'value'
            ]
        );

        $this->client->request(
            Request::METHOD_GET,
            sprintf('/test/launch?%s', http_build_query($launchRequest->getParameters()))
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(
            [
                'resourceLinkId' => 'resourceLinkIdentifier',
                'contextId' => 'contextId',
                'userId' => null,
                'roles' => ['roles'],
                'custom' => 'value'
            ],
            $responseData['claims']
        );

        $this->assertEquals(
            [
                'JWT id_token signature validation success',
                'JWT id_token is not expired',
                'JWT id_token nonce is valid',
                'JWT id_token deployment_id claim valid for this registration'
            ],
            $responseData['validations']['successes']
        );

        $this->assertEmpty($responseData['validations']['failures']);
        $this->assertEmpty($responseData['credentials']);
    }

    public function testItCanHandleUserLtiLaunchRequest(): void
    {
        $builder = static::$container->get(LtiLaunchRequestBuilder::class);

        $registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');

        $launchRequest = $builder->buildUserResourceLinkLtiLaunchRequest(
            new ResourceLink('resourceLinkIdentifier'),
            $registration,
            new UserIdentity('userIdentifier'),
            null,
            [
                'roles'
            ],
            [
                new ContextClaim('contextId'),
                'custom' => 'value'
            ]
        );

        $this->client->request(
            Request::METHOD_GET,
            sprintf('/test/launch?%s', http_build_query($launchRequest->getParameters()))
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(
            [
                'resourceLinkId' => 'resourceLinkIdentifier',
                'contextId' => 'contextId',
                'userId' => 'userIdentifier',
                'roles' => ['roles'],
                'custom' => 'value'
            ],
            $responseData['claims']
        );

        $this->assertEquals(
            [
                'JWT id_token signature validation success',
                'JWT id_token is not expired',
                'JWT id_token nonce is valid',
                'JWT id_token deployment_id claim valid for this registration'
            ],
            $responseData['validations']['successes']
        );

        $this->assertEmpty($responseData['validations']['failures']);
        $this->assertEmpty($responseData['credentials']);
    }

    public function testItReturnsUnauthorizedResponseWithoutIdToken(): void
    {
        $this->client->request(Request::METHOD_GET, '/test/launch');

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testItReturnsUnauthorizedResponseWithEmptyIdToken(): void
    {
        $this->client->request(Request::METHOD_GET, '/test/launch?id_token=');

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString(
            'LTI launch request authentication failed: LTI message validation failed: The JWT string must have two dots',
            (string)$response->getContent()
        );
    }

    public function testItReturnsUnauthorizedResponseWithExpiredIdToken(): void
    {
        $now = Carbon::now();

        Carbon::setTestNow($now->subSeconds(LtiMessageInterface::TTL + 1));

        $builder = static::$container->get(LtiLaunchRequestBuilder::class);

        $registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');

        $launchRequest = $builder->buildResourceLinkLtiLaunchRequest(
            new ResourceLink('resourceLinkIdentifier'),
            $registration
        );

        Carbon::setTestNow($now);

        $this->client->request(
            Request::METHOD_GET,
            sprintf('/test/launch?%s', http_build_query($launchRequest->getParameters()))
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString(
            'LTI launch request authentication failed: JWT id_token is expired',
            (string)$response->getContent()
        );
    }
}
