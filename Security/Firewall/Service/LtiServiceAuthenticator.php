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

namespace OAT\Bundle\Lti1p3Bundle\Security\Firewall\Service;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LtiServiceAuthenticator extends AbstractAuthenticator
{
    private HttpMessageFactoryInterface $factory;

    private FirewallMap $firewallMap;

    private $validator;

    private string $firewallName;

    /** string[] */
    private array $scopes;

    public function __construct(
        FirewallMap $firewallMap,
        HttpMessageFactoryInterface $factory,
        RequestAccessTokenValidatorInterface $validator,
        string $firewallName,
        array $scopes = []
    ) {
        $this->factory = $factory;
        $this->firewallMap = $firewallMap;
        $this->validator = $validator;
        $this->firewallName = $firewallName;
        $this->scopes = $scopes;
    }

    public function supports(Request $request): ?bool
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        return $request->headers->has('Authorization') && $firewallConfig?->getName() === $this->firewallName;
    }

    public function authenticate(Request $request): Passport
    {
        if (!$request->headers->has('Authorization')) {
            throw new AuthenticationException('Authorization header is missing');
        }

        $username = 'lti-service';

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
            $validationResult = $this->validator->validate(
                $passport->getAttribute('request'),
                $this->scopes
            );

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            $token = new LtiServiceSecurityToken($validationResult);
            $token->setAttribute('request', $passport->getAttribute('request'));
            $token->setAttribute('firewall_config', $passport->getAttribute('firewall_config'));

            return $token;
        } catch (\Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI service request authentication failed: %s', $exception->getMessage()),
                (int) $exception->getCode(),
                $exception
            );
        }
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
