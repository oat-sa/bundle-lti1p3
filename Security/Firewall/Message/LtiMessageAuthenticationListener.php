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

use Lcobucci\JWT\Parser;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiMessageToken;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Jwt\AssociativeDecoder;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Throwable;

class LtiMessageAuthenticationListener extends AbstractListener
{
    /** @var TokenStorageInterface */
    private $storage;

    /** @var AuthenticationManagerInterface  */
    private $manager;

    /** @var HttpMessageFactoryInterface */
    private $factory;

    /** @var Parser */
    private $parser;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        HttpMessageFactoryInterface $factory
    ) {
        $this->storage = $tokenStorage;
        $this->manager = $authenticationManager;
        $this->factory = $factory;
        $this->parser = new Parser(new AssociativeDecoder());
    }

    public function supports(Request $request): ?bool
    {
        return null !== $request->get('id_token');
    }

    public function authenticate(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $token = new LtiMessageToken();
        $token->setAttribute('request', $this->factory->createRequest($request));

        try {
            $this->storage->setToken($this->manager->authenticate($token));
        } catch (Throwable $exception) {
            $event->setResponse($this->handleErrorDelegation($exception, $request));
        }
    }

    /**
     * @throws Throwable
     */
    private function handleErrorDelegation(Throwable $exception, Request $request): RedirectResponse
    {
        try {
            $message = new LtiMessage($this->parser->parse($request->get('id_token')));
        } catch (Throwable $parseException) {
            throw new AuthenticationException(
                sprintf('LTI message request authentication failed: %s', $parseException->getMessage()),
                $parseException->getCode(),
                $parseException
            );
        }

        if (null !== $message->getLaunchPresentation() && null !== $message->getLaunchPresentation()->getReturnUrl()) {
            $redirectUrl = sprintf(
                '%s%slti_errormsg=%s',
                $message->getLaunchPresentation()->getReturnUrl(),
                strpos($message->getLaunchPresentation()->getReturnUrl(), '?') ? '&' : '?',
                urlencode($exception->getMessage())
            );

            return new RedirectResponse($redirectUrl);
        }

        throw $exception;
    }
}
