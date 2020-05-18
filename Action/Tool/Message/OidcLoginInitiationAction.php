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

namespace OAT\Bundle\Lti1p3Bundle\Action\Tool\Message;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginInitiator;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OidcLoginInitiationAction
{
    /** @var HttpMessageFactoryInterface */
    private $factory;

    /** @var OidcLoginInitiator */
    private $initiator;

    public function __construct(HttpMessageFactoryInterface $factory, OidcLoginInitiator $initiator)
    {
        $this->factory = $factory;
        $this->initiator = $initiator;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $oidcAuthenticationRequest = $this->initiator->initiate($this->factory->createRequest($request));

            return new RedirectResponse($oidcAuthenticationRequest->toUrl());
        } catch (LtiException $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
