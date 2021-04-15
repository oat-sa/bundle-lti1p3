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

namespace OAT\Bundle\Lti1p3Bundle\Service\Server\Factory;

use OAT\Bundle\Lti1p3Bundle\Service\Server\Handler\LtiServiceServerHttpFoundationRequestHandler;
use OAT\Bundle\Lti1p3Bundle\Service\Server\Handler\LtiServiceServerHttpFoundationRequestHandlerInterface;
use OAT\Library\Lti1p3Core\Service\Server\Handler\LtiServiceServerRequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Security\Core\Security;

class LtiServiceServerHttpFoundationRequestHandlerFactory implements LtiServiceServerHttpFoundationRequestHandlerFactoryInterface
{
    /** @var Security */
    private $security;

    /** @var HttpFoundationFactoryInterface */
    private $httpFoundationFactory;

    /** @var HttpMessageFactoryInterface */
    private $psr7Factory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Security $security,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        HttpMessageFactoryInterface $psr7Factory,
        LoggerInterface $logger
    ) {
        $this->security = $security;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->psr7Factory = $psr7Factory;
        $this->logger = $logger;
    }

    public function create(
        LtiServiceServerRequestHandlerInterface $handler,
        array $options = []
    ): LtiServiceServerHttpFoundationRequestHandlerInterface {
        return new LtiServiceServerHttpFoundationRequestHandler(
            $handler,
            $this->security,
            $this->httpFoundationFactory,
            $this->psr7Factory,
            $this->logger,
            $options
        );
    }
}
