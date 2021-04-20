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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Service\Server\Handler;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Core\Service\Server\Handler\LtiServiceServerRequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Security\Core\Security;
use Throwable;

class LtiServiceServerHttpFoundationRequestHandler implements LtiServiceServerHttpFoundationRequestHandlerInterface
{
    /** @var LtiServiceServerRequestHandlerInterface */
    private $handler;

    /** @var Security */
    private $security;

    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /** @var HttpMessageFactoryInterface */
    private $psr7Factory;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $options;

    public function __construct(
        LtiServiceServerRequestHandlerInterface $handler,
        Security $security,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        HttpMessageFactoryInterface $psr7Factory,
        LoggerInterface $logger,
        array $options = []
    ) {
        $this->handler = $handler;
        $this->security = $security;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->psr7Factory = $psr7Factory;
        $this->logger = $logger;
        $this->options = $options;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(Request $request): Response
    {
        $allowedMethods = array_map('strtolower', $this->handler->getAllowedMethods());

        if (!empty($allowedMethods) && !in_array(strtolower($request->getMethod()), $allowedMethods)) {
            $message = sprintf('Not acceptable request method, accepts: [%s]', implode(', ', $allowedMethods));
            $this->logger->error($message);

            throw new MethodNotAllowedHttpException($this->handler->getAllowedMethods(), $message);
        }

        $allowedContentType = $this->handler->getAllowedContentType();

        $contentTypeHeader = 'get' === strtolower($request->getMethod()) ? 'Accept' : 'Content-Type';

        if (
            !empty($allowedContentType)
            && false === strpos($request->headers->get($contentTypeHeader, ''), $allowedContentType)
        ) {
            $message = sprintf('Not acceptable request content type, accepts: %s', $allowedContentType);
            $this->logger->error($message);

            throw new NotAcceptableHttpException($message);
        }

        try {
            /** @var LtiServiceSecurityToken $token */
            $token = $this->security->getToken();

            $handlerResponse = $this->handler->handleValidatedServiceRequest(
                $token->getValidationResult(),
                $this->psr7Factory->createRequest($request),
                $this->options
            );

            $this->logger->info(sprintf('%s service success', $this->handler->getServiceName()));

            return $this->httpFoundationFactory->createResponse($handlerResponse);
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf(
                    '%s service error: %s',
                    $this->handler->getServiceName(),
                    $exception->getMessage()
                )
            );

            throw $exception;
        }
    }
}
