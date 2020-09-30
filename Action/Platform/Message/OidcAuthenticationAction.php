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

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OidcAuthenticationAction
{
    /** @var HttpMessageFactoryInterface */
    private $factory;

    /** @var OidcAuthenticator */
    private $authenticator;

    public function __construct(HttpMessageFactoryInterface $factory, OidcAuthenticator $authenticator)
    {
        $this->factory = $factory;
        $this->authenticator = $authenticator;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $launchRequest = $this->authenticator->authenticate($this->factory->createRequest($request));

            return new Response($launchRequest->toHtmlRedirectForm());

        } catch (LtiExceptionInterface $exception) {
            return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }
}
