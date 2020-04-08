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

namespace OAT\Bundle\Lti1p3Bundle\Action\Tool;

use OAT\Library\Lti1p3Core\Security\Oidc\Endpoint\OidcLoginInitiator;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

    public function __invoke(Request $request): JsonResponse
    {
        var_dump($this->initiator->initiate($this->factory->createRequest($request)));
    }
}
