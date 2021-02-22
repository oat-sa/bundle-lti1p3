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
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilderInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Tests\Traits\SecurityTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/message/platform-originating-messages.md
 */
class LtiPlatformOriginatingMessageFlowTest extends WebTestCase
{
    use SecurityTestingTrait;

    /** @var KernelBrowser */
    private $client;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    /** @var RegistrationInterface */
    private $registration;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->builder = static::$container->get(LtiResourceLinkLaunchRequestBuilder::class);

        $this->registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testItCanHandleAPlatformOriginatingMessage(): void
    {
        // Step 1 - Platform LTI Resource Link launch request

        $message = $this->builder->buildLtiResourceLinkLaunchRequest(
            new LtiResourceLink('resourceLinkIdentifier'),
            $this->registration,
            'loginHint',
            null,
            [
                'roles'
            ],
            [
                new ContextClaim('contextIdentifier'),
                'custom' => 'value'
            ]
        );

        // Step 2 - Tool OIDC initiation

        $oidcInitLocation = parse_url($this->registration->getTool()->getOidcInitiationUrl());

        $this->client->request(
            Request::METHOD_GET,
            sprintf('%s?%s',$oidcInitLocation['path'], http_build_query($message->getParameters()->all()))
        );

        $oidcInitResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FOUND, $oidcInitResponse->getStatusCode());

        // Step 3 - Platform OIDC authentication

        $oidcAuthLocation = parse_url($oidcInitResponse->headers->get('location'));

        $this->client->request(
            Request::METHOD_GET,
            sprintf('%s?%s', $oidcAuthLocation['path'], $oidcAuthLocation['query'])
        );

        $oidcAuthResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $oidcAuthResponse->getStatusCode());

        $crawler = $this->client->getCrawler();

        $action = $crawler->filterXPath('//body/form')->attr('action');
        $payload = $crawler->filterXPath('//body/form/input[@name="id_token"]')->attr('value');
        $state = $crawler->filterXPath('//body/form/input[@name="state"]')->attr('value');

        $this->assertEquals($this->registration->getTool()->getLaunchUrl(), $action);

        // Step 4 - Tool message validation

        $toolLocation = parse_url($action);

        $this->client->request(
            Request::METHOD_POST,
            $toolLocation['path'],
            [
                'id_token' => $payload,
                'state' => $state,
            ]
        );

        $toolResponseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('testRegistration', $toolResponseData['registration']);

        $this->assertEquals(
            [
                'resourceLinkId' => 'resourceLinkIdentifier',
                'contextId' => 'contextIdentifier',
                'userId' => 'loginHint',
                'roles' => [
                        0 => 'roles',
                    ],
                'custom' => 'value',
            ],
            $toolResponseData['claims']
        );

        $this->assertEquals(
            [
                'successes' => [
                    'ID token validation success',
                    'ID token kid header is provided',
                    'ID token version claim is valid',
                    'ID token message_type claim is valid',
                    'ID token roles claim is valid',
                    'ID token user identifier (sub) claim is valid',
                    'ID token nonce claim is valid',
                    'ID token deployment_id claim valid for this registration',
                    'ID token message type claim LtiResourceLinkRequest requirements are valid',
                    'State validation success',
                    ],
                'error' => NULL,
            ],
            $toolResponseData['validations']
        );

        $this->assertTrue(
            $this->verifyJwt(
                $this->parseJwt($toolResponseData['credentials']),
                $this->registration->getPlatformKeyChain()->getPublicKey()
            )
        );

        $this->assertTrue(
            $this->verifyJwt(
                $this->parseJwt($toolResponseData['state']),
                $this->registration->getToolKeyChain()->getPublicKey()
            )
        );
    }

    public function testItFailsWithExpiredIdToken(): void
    {
        // Step 1 - Platform LTI Resource Link launch request

        $message = $this->builder->buildLtiResourceLinkLaunchRequest(
            new LtiResourceLink('resourceLinkIdentifier'),
            $this->registration,
            'loginHint',
            null,
            [
                'roles'
            ],
            [
                new ContextClaim('contextIdentifier'),
                'custom' => 'value'
            ]
        );

        // Step 2 - Tool OIDC initiation

        $oidcInitLocation = parse_url($this->registration->getTool()->getOidcInitiationUrl());

        $this->client->request(
            Request::METHOD_GET,
            sprintf('%s?%s',$oidcInitLocation['path'], http_build_query($message->getParameters()->all()))
        );

        $oidcInitResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FOUND, $oidcInitResponse->getStatusCode());

        // Step 3 - Platform OIDC authentication

        $oidcAuthLocation = parse_url($oidcInitResponse->headers->get('location'));

        $this->client->request(
            Request::METHOD_GET,
            sprintf('%s?%s', $oidcAuthLocation['path'], $oidcAuthLocation['query'])
        );

        $oidcAuthResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $oidcAuthResponse->getStatusCode());

        $crawler = $this->client->getCrawler();

        $action = $crawler->filterXPath('//body/form')->attr('action');
        $payload = $crawler->filterXPath('//body/form/input[@name="id_token"]')->attr('value');
        $state = $crawler->filterXPath('//body/form/input[@name="state"]')->attr('value');

        $this->assertEquals($this->registration->getTool()->getLaunchUrl(), $action);

        // Step 4 - Tool message validation

        /** @var MessagePayloadBuilderInterface $messagePayloadBuilder */
        $messagePayloadBuilder =  static::$container->get(MessagePayloadBuilderInterface::class);

        Carbon::setTestNow(Carbon::now()->subSeconds(LtiMessagePayloadInterface::TTL + 1));

        $expiredPayload = $messagePayloadBuilder
            ->withClaims($this->parseJwt($payload)->getClaims()->all())
            ->buildMessagePayload($this->registration->getPlatformKeyChain());

        Carbon::setTestNow();

        $toolLocation = parse_url($action);

        $this->client->request(
            Request::METHOD_POST,
            $toolLocation['path'],
            [
                'id_token' => $expiredPayload->getToken()->toString(),
                'state' => $state,
            ]
        );

        $toolResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $toolResponse->getStatusCode());
        $this->assertStringContainsString(
            'LTI tool message request authentication failed: ID token validation failure',
            $toolResponse->getContent()
        );
    }

    public function testItFailsWithExpiredIdTokenAndRedirection(): void
    {
        // Step 1 - Platform LTI Resource Link launch request

        $message = $this->builder->buildLtiResourceLinkLaunchRequest(
            new LtiResourceLink('resourceLinkIdentifier'),
            $this->registration,
            'loginHint',
            null,
            [
                'roles'
            ],
            [
                new ContextClaim('contextIdentifier'),
                new LaunchPresentationClaim(null, null, null, 'http://redirect.com'),
                'custom' => 'value'
            ]
        );

        // Step 2 - Tool OIDC initiation

        $oidcInitLocation = parse_url($this->registration->getTool()->getOidcInitiationUrl());

        $this->client->request(
            Request::METHOD_GET,
            sprintf('%s?%s',$oidcInitLocation['path'], http_build_query($message->getParameters()->all()))
        );

        $oidcInitResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FOUND, $oidcInitResponse->getStatusCode());

        // Step 3 - Platform OIDC authentication

        $oidcAuthLocation = parse_url($oidcInitResponse->headers->get('location'));

        $this->client->request(
            Request::METHOD_GET,
            sprintf('%s?%s', $oidcAuthLocation['path'], $oidcAuthLocation['query'])
        );

        $oidcAuthResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $oidcAuthResponse->getStatusCode());

        $crawler = $this->client->getCrawler();

        $action = $crawler->filterXPath('//body/form')->attr('action');
        $payload = $crawler->filterXPath('//body/form/input[@name="id_token"]')->attr('value');
        $state = $crawler->filterXPath('//body/form/input[@name="state"]')->attr('value');

        $this->assertEquals($this->registration->getTool()->getLaunchUrl(), $action);

        // Step 4 - Tool message validation

        /** @var MessagePayloadBuilderInterface $messagePayloadBuilder */
        $messagePayloadBuilder =  static::$container->get(MessagePayloadBuilderInterface::class);

        Carbon::setTestNow(Carbon::now()->subSeconds(LtiMessagePayloadInterface::TTL + 1));

        $expiredPayload = $messagePayloadBuilder
            ->withClaims($this->parseJwt($payload)->getClaims()->all())
            ->buildMessagePayload($this->registration->getPlatformKeyChain());

        Carbon::setTestNow();

        $toolLocation = parse_url($action);

        $this->client->request(
            Request::METHOD_POST,
            $toolLocation['path'],
            [
                'id_token' => $expiredPayload->getToken()->toString(),
                'state' => $state,
            ]
        );

        $toolResponse = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $toolResponse->getStatusCode());
        $this->assertEquals(
            'http://redirect.com?lti_errormsg=LTI+tool+message+request+authentication+failed%3A+ID+token+validation+failure',
            $toolResponse->headers->get('location')
        );
    }
}
