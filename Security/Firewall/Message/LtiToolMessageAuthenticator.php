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
 * Copyright (c) 2024 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Security\Firewall\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiPlatformMessageSecurityToken;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Bundle\Lti1p3Bundle\Security\Exception\LtiToolMessageExceptionHandlerInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LtiToolMessageAuthenticator extends AbstractAuthenticator
{
    private HttpMessageFactoryInterface $factory;

    private LtiToolMessageExceptionHandlerInterface $handler;

    private FirewallMap $firewallMap;

    private ToolLaunchValidatorInterface $validator;

    private string $firewallName;

    private array $types;


    public function __construct(
        FirewallMap $firewallMap,
        HttpMessageFactoryInterface $factory,
        LtiToolMessageExceptionHandlerInterface $handler,
        ToolLaunchValidatorInterface $validator,
        string $firewallName,
        array $types = []
    ) {
        $this->factory = $factory;
        $this->handler = $handler;
        $this->firewallMap = $firewallMap;
        $this->validator = $validator;
        $this->firewallName = $firewallName;
        $this->types = $types;
    }

    public function supports(Request $request): ?bool
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        return null !== $this->getIdTokenFromRequest($request) && $firewallConfig?->getName() === $this->firewallName;
    }

    public function authenticate(Request $request): Passport
    {
//        $request = $event->getRequest();

//        $token = new LtiToolMessageSecurityToken();
//        $token->setAttribute('request', $this->factory->createRequest($request));
//        $token->setAttribute('firewall_config', $this->firewallMap->getFirewallConfig($request));

        $username = 'lti-tool';

        $passport = new SelfValidatingPassport(new UserBadge($username), [
            new PreAuthenticatedUserBadge()
        ]);

        $passport->setAttribute('request', $this->factory->createRequest($request));
        $passport->setAttribute('firewall_config', $this->firewallMap->getFirewallConfig($request));

        return $passport;
    }
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        try {
            $validationResult = $this->validator->validatePlatformOriginatingLaunch($passport->getAttribute('request'));

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            $messageType = $validationResult->getPayload()->getMessageType();

            if (!empty($this->types) && !in_array($messageType, $this->types)) {
                throw new BadRequestHttpException(sprintf('Invalid LTI message type %s', $messageType));
            }

            $token = new LtiToolMessageSecurityToken($validationResult);
            $token->setAttribute('request', $passport->getAttribute('request'));
            $token->setAttribute('firewall_config', $passport->getAttribute('firewall_config'));

            return $token;
        } catch (BadRequestHttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI tool message request authentication failed: %s', $exception->getMessage()),
                (int) $exception->getCode(),
                $exception
            );
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

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->handler->handle($exception, $request);
//        return new JsonResponse([
//            'error' => [
//                'message' => strtr($exception->getMessage(), $exception->getMessageData()),
//            ],
//        ], Response::HTTP_UNAUTHORIZED);
    }
}
