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

namespace OAT\Bundle\Lti1p3Bundle\Action\Platform\Service;

use League\OAuth2\Server\Exception\OAuthServerException;
use OAT\Library\Lti1p3Core\Security\OAuth2\Generator\AccessTokenResponseGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuth2AccessTokenCreationAction
{
    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /** @var HttpMessageFactoryInterface */
    private $psr7Factory;

    /** @var AccessTokenResponseGenerator */
    private $generator;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        HttpFoundationFactoryInterface $httpFoundationFactory,
        HttpMessageFactoryInterface $psr7Factory,
        AccessTokenResponseGenerator $generator,
        LoggerInterface $logger
    ) {
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->psr7Factory = $psr7Factory;
        $this->generator = $generator;
        $this->logger = $logger;
    }

    public function __invoke(Request $request, string $keyChainIdentifier): Response
    {
        $psr7Response = $this->psr7Factory->createResponse(new Response());

        try {
            $psr7AuthenticationResponse = $this->generator->generate(
                $this->psr7Factory->createRequest($request),
                $psr7Response,
                $keyChainIdentifier
            );

            $this->logger->info('OAuth2AccessTokenCreationAction: access token generation success');

            return $this->httpFoundationFactory->createResponse($psr7AuthenticationResponse);

        } catch (OAuthServerException $exception) {
            $this->logger->error(sprintf('OAuth2AccessTokenCreationAction: %s', $exception->getMessage()));

            return $this->httpFoundationFactory->createResponse($exception->generateHttpResponse($psr7Response));
        }
    }
}
