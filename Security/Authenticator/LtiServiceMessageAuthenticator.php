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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authenticator;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidatorInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Throwable;

class LtiServiceMessageAuthenticator extends AbstractAuthenticator
{
    /** @var HttpMessageFactoryInterface */
    private $httpMessageFactory;

    /** @var FirewallMap */
    private $firewallMap;

    /** @var RequestAccessTokenValidatorInterface */
    private $requestAccessTokenValidator;

    /** @var string */
    private $firewallName;

    /** @var string[] */
    private $scopes;

    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        FirewallMap $firewallMap,
        RequestAccessTokenValidatorInterface $requestAccessTokenValidator,
        string $firewallName,
        array $scopes = []
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->firewallMap = $firewallMap;
        $this->requestAccessTokenValidator = $requestAccessTokenValidator;
        $this->firewallName = $firewallName;
        $this->scopes = $scopes;
    }

    public function supports(Request $request): ?bool
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        if (null === $firewallConfig) {
            return false;
        }

        return $this->firewallName === $firewallConfig->getName();
    }

    public function authenticate(Request $request)
    {
        try {
            $validationResult = $this->requestAccessTokenValidator->validate(
                $this->httpMessageFactory->createRequest($request),
                $this->scopes
            );

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            $passport = new SelfValidatingPassport(new UserBadge('lti', function () {
                return new InMemoryUser('lti', null);
            }));

            $passport->setAttribute('validationResult', $validationResult);

            return $passport;
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI service request authentication failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new LtiServiceSecurityToken($passport->getAttribute('validationResult'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
}
