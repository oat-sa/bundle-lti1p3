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

namespace OAT\Bundle\Lti1p3Bundle\Action\Jwks;

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Server\JwksServer;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class JwksAction
{
    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /** @var JwksExporter */
    private $server;

    public function __construct(HttpFoundationFactoryInterface $httpFoundationFactory, JwksServer $server)
    {
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->server = $server;
    }

    public function __invoke(string $keySetName): Response
    {
        return $this->httpFoundationFactory->createResponse($this->server->handle($keySetName));
    }
}
