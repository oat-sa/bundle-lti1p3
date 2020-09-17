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

use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcAuthenticationServer;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OidcAuthenticationAction
{
    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /** @var HttpMessageFactoryInterface */
    private $psr7Factory;

    /** @var OidcAuthenticationServer */
    private $server;

    public function __construct(
        HttpFoundationFactoryInterface $httpFoundationFactory,
        HttpMessageFactoryInterface $psr7Factory,
        OidcAuthenticationServer $server
    ) {
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->psr7Factory = $psr7Factory;
        $this->server = $server;
    }

    public function __invoke(Request $request): Response
    {
        return $this->httpFoundationFactory->createResponse(
            $this->server->handle($this->psr7Factory->createRequest($request))
        );
    }
}
