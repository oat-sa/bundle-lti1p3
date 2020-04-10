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

namespace OAT\Bundle\Lti1p3Bundle\Security\Firewall;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\LtiLaunchRequestToken;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LtiLaunchRequestAuthenticationListener
{
    /** @var TokenStorageInterface */
    private $storage;

    /** @var AuthenticationManagerInterface  */
    private $manager;

    /** @var HttpMessageFactoryInterface */
    private $factory;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        HttpMessageFactoryInterface $factory
    ) {
        $this->storage = $tokenStorage;
        $this->manager = $authenticationManager;
        $this->factory = $factory;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (null === $request->get('id_token')) {
            return;
        }

        $token = new LtiLaunchRequestToken();
        $token->setAttribute('request', $this->factory->createRequest($request));

        $this->storage->setToken($this->manager->authenticate($token));
    }
}
