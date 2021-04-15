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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Tests\Resources\Action\Platform\Service;

use OAT\Bundle\Lti1p3Bundle\Service\Server\Factory\LtiServiceServerHttpFoundationRequestHandlerFactoryInterface;
use OAT\Bundle\Lti1p3Bundle\Tests\Resources\Service\Server\Handler\TestServiceRequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestServiceAction
{
    /** @var TestServiceRequestHandler */
    private $handler;

    /** @var LtiServiceServerHttpFoundationRequestHandlerFactoryInterface */
    private $factory;

    public function __construct(
        TestServiceRequestHandler $handler,
        LtiServiceServerHttpFoundationRequestHandlerFactoryInterface $factory
    ) {
        $this->handler = $handler;
        $this->factory = $factory;
    }

    public function __invoke(Request $request): Response
    {
        $shouldThrowException = $request->get('shouldThrowException');

        $handler = $this->factory->create(
            $this->handler,
            [
                'shouldThrowException' => $shouldThrowException
            ]
        );

        return $handler($request);
    }
}
