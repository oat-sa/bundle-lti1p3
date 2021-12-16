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

namespace OAT\Bundle\Lti1p3Bundle\Action\Platform\Message;

use Throwable;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OidcAuthenticationAction
{
    /** @var HttpMessageFactoryInterface */
    private $factory;

    /** @var OidcAuthenticator */
    private $authenticator;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        HttpMessageFactoryInterface $factory,
        OidcAuthenticator $authenticator,
        LoggerInterface $logger
    ) {
        $this->factory = $factory;
        $this->authenticator = $authenticator;
        $this->logger = $logger;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $launchRequest = $this->authenticator->authenticate($this->factory->createRequest($request));

            $this->logger->info('OidcAuthenticationAction: authentication success');

            return new Response($launchRequest->toHtmlRedirectForm());

        } catch (LtiExceptionInterface $exception) {
            $this->logger->error(sprintf('OidcAuthenticationAction: %s', $exception->getMessage()));

            return new Response(
                $this->convertThrowableMessageToHtml($exception),
                Response::HTTP_UNAUTHORIZED
            );
        }
    }

    private function convertThrowableMessageToHtml(Throwable $throwable): string
    {
        return htmlspecialchars($throwable->getMessage(), ENT_QUOTES);
    }
}
