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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Functional\Flow\Message;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\ToolOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/tool-originating-messages.md
 */
class LtiToolOriginatingMessageFlowTest extends WebTestCase
{
    use SecurityTestingTrait;

    /** @var KernelBrowser */
    private $client;

    /** @var ToolOriginatingLaunchBuilder */
    private $builder;

    /** @var RegistrationInterface */
    private $registration;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->builder = static::getContainer()->get(ToolOriginatingLaunchBuilder::class);

        $this->registration = static::getContainer()
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testItCanHandleAToolOriginatingMessage(): void
    {
        // Step 1 - Tool message generation

        $dlData = $this
            ->buildJwt(
                [],
                [],
                $this->registration->getPlatformKeyChain()->getPrivateKey()
            )
            ->toString();

        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            '/test/message/platform',
            null,
            [
                'custom' => 'value',
                LtiMessagePayloadInterface::CLAIM_LTI_DEEP_LINKING_DATA => $dlData
            ]
        );

        // Step 2 - Platform message validation

        $this->client->request(Request::METHOD_GET, $message->toUrl());

        $platformResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $platformResponse->getStatusCode());

        $platformResponseData = json_decode($platformResponse->getContent(), true);

        $this->assertEquals('testRegistration', $platformResponseData['registration']);

        $this->assertEquals(
            [
                'type' => LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
                'custom' => 'value',
            ],
            $platformResponseData['claims']
        );

        $this->assertEquals(
            [
                'successes' => [
                    'JWT kid header is provided',
                    'JWT validation success',
                    'JWT version claim is valid',
                    'JWT message_type claim is valid',
                    'JWT nonce claim is valid',
                    'JWT deployment_id claim valid for this registration',
                    'JWT message type claim LtiDeepLinkingResponse requirements are valid',
                ],
                'error' => NULL,
            ],
            $platformResponseData['validations']
        );

        $this->assertTrue(
            $this->verifyJwt(
                $this->parseJwt($platformResponseData['credentials']),
                $this->registration->getToolKeyChain()->getPublicKey()
            )
        );
    }

    public function testItFailsWithExpiredJwt(): void
    {
        // Step 1 - Tool message generation in the past

        Carbon::setTestNow(Carbon::now()->subSeconds(LtiMessagePayloadInterface::TTL + 1));

        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE,
            '/test/message/platform',
            null,
            [
                'custom' => 'value'
            ]
        );

        Carbon::setTestNow();

        // Step 2 - Platform message validation

        $this->client->request(Request::METHOD_GET, $message->toUrl());

        $platformResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $platformResponse->getStatusCode());
        $this->assertStringContainsString(
            'LTI platform message request authentication failed: JWT validation failure',
            $platformResponse->getContent()
        );
    }

    public function testItFailsWithInvalidLtiMessageType(): void
    {
        // Step 1 - Tool startAssessment message generation

        $resourceLinkClaim = new ResourceLinkClaim('identifier');

        $securityToken = $this->buildJwt([], [], $this->registration->getPlatformKeyChain()->getPrivateKey());

        $message = $this->builder->buildToolOriginatingLaunch(
            $this->registration,
            LtiMessageInterface::LTI_MESSAGE_TYPE_START_ASSESSMENT,
            '/test/message/platform',
            null,
            [
                $resourceLinkClaim,
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_SESSION_DATA => $securityToken->toString(),
                LtiMessagePayloadInterface::CLAIM_LTI_PROCTORING_ATTEMPT_NUMBER => 1,
            ]
        );

        // Step 2 - Platform message validation

        $this->client->request(Request::METHOD_GET, $message->toUrl());

        $platformResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $platformResponse->getStatusCode());
        $this->assertStringContainsString('Invalid LTI message type LtiStartAssessment', $platformResponse->getContent());
    }
}
