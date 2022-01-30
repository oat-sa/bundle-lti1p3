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

namespace OAT\Bundle\Lti1p3Bundle\Security\Firewall\Service;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\AbstractListener;

/**
 * @deprecated since 6.1.1, AuthenticatorManagerListener is used automatically instead by the new authenticator system,
 * internal logic moved to LtiServiceMessageAuthenticator.
 */
class LtiServiceAuthenticationListener extends AbstractListener
{
    /** @var TokenStorageInterface */
    private $storage;

    /** @var AuthenticationManagerInterface  */
    private $manager;

    /** @var HttpMessageFactoryInterface */
    private $factory;

    /** @var FirewallMap */
    private $firewallMap;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        HttpMessageFactoryInterface $factory,
        FirewallMap $firewallMap
    ) {
        $this->storage = $tokenStorage;
        $this->manager = $authenticationManager;
        $this->factory = $factory;
        $this->firewallMap = $firewallMap;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $token = new LtiServiceSecurityToken();
        $token->setAttribute('request', $this->factory->createRequest($request));
        $token->setAttribute('firewall_config', $this->firewallMap->getFirewallConfig($request));

        $this->storage->setToken($this->manager->authenticate($token));
    }
}
