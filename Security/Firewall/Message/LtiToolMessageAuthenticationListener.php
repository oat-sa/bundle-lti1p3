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

namespace OAT\Bundle\Lti1p3Bundle\Security\Firewall\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Bundle\Lti1p3Bundle\Security\Exception\LtiToolMessageExceptionHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Throwable;

/**
 * @deprecated since 6.1.1, AuthenticatorManagerListener is used automatically instead by the new authenticator system,
 * internal logic moved to LtiToolMessageAuthenticator.
 */
class LtiToolMessageAuthenticationListener extends AbstractListener
{
    /** @var TokenStorageInterface */
    private $storage;

    /** @var AuthenticationManagerInterface  */
    private $manager;

    /** @var HttpMessageFactoryInterface */
    private $factory;

    /** @var LtiToolMessageExceptionHandlerInterface */
    private $handler;

    /** @var FirewallMap */
    private $firewallMap;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        HttpMessageFactoryInterface $factory,
        LtiToolMessageExceptionHandlerInterface $handler,
        FirewallMap $firewallMap
    ) {
        $this->storage = $tokenStorage;
        $this->manager = $authenticationManager;
        $this->factory = $factory;
        $this->handler = $handler;
        $this->firewallMap = $firewallMap;
    }

    public function supports(Request $request): ?bool
    {
        return null !== $this->getIdTokenFromRequest($request);
    }

    public function authenticate(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $token = new LtiToolMessageSecurityToken();
        $token->setAttribute('request', $this->factory->createRequest($request));
        $token->setAttribute('firewall_config', $this->firewallMap->getFirewallConfig($request));

        try {
            $this->storage->setToken($this->manager->authenticate($token));
        } catch (Throwable $exception) {
            $event->setResponse($this->handler->handle($exception, $request));
        }
    }

    private function getIdTokenFromRequest(Request $request): ?string
    {
        $idTokenFromQuery = $request->query->get('id_token');
        if (null !== $idTokenFromQuery) {
            return $idTokenFromQuery;
        }

        return $request->request->get('id_token');
    }
}
