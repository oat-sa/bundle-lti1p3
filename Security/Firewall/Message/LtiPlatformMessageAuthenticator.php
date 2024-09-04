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
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidatorInterface;
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

class LtiPlatformMessageAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private FirewallMap $firewallMap,
        private HttpMessageFactoryInterface $factory,
        private PlatformLaunchValidatorInterface $validator,
        private string $firewallName,
        private array $types = []
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        return null !== $this->getJwtFromRequest($request) && $firewallConfig?->getName() === $this->firewallName;
    }

    public function authenticate(Request $request): Passport
    {
        $username = 'lti-platform';

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
            $validationResult = $this->validator->validateToolOriginatingLaunch($passport->getAttribute('request'));

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            $messageType = $validationResult->getPayload()?->getMessageType();

            if (!empty($this->types) && !in_array($messageType, $this->types)) {
                throw new BadRequestHttpException(sprintf('Invalid LTI message type %s', $messageType));
            }

            $token = new LtiPlatformMessageSecurityToken($validationResult);
            $token->setAttribute('request', $passport->getAttribute('request'));
            $token->setAttribute('firewall_config', $passport->getAttribute('firewall_config'));

            return $token;
        } catch (BadRequestHttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI platform message request authentication failed: %s', $exception->getMessage()),
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    private function getJwtFromRequest(Request $request): ?string
    {
        $jwtFromQuery = $request->query->get('JWT');
        if (null !== $jwtFromQuery) {
            return $jwtFromQuery;
        }

        return $request->request->get('JWT');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => [
                'message' => strtr($exception->getMessage(), $exception->getMessageData()),
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
