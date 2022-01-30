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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Provider\Service;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

/**
 * @deprecated since 6.1.1, use LtiServiceMessageAuthenticator instead
 */
class LtiServiceAuthenticationProvider implements AuthenticationProviderInterface
{
    /** @var RequestAccessTokenValidatorInterface */
    private $validator;

    /** string */
    private $firewallName;

    /** string[] */
    private $scopes;

    public function __construct(RequestAccessTokenValidatorInterface $validator, string $firewallName, array $scopes = [])
    {
        $this->validator = $validator;
        $this->firewallName = $firewallName;
        $this->scopes = $scopes;
    }

    public function supports(TokenInterface $token): bool
    {
        $firewallName = $token->hasAttribute('firewall_config') ? $token->getAttribute('firewall_config')->getName() : null;

        return $token instanceof LtiServiceSecurityToken && $firewallName === $this->firewallName;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        try {
            $validationResult = $this->validator->validate(
                $token->getAttribute('request'),
                $this->scopes
            );

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            return new LtiServiceSecurityToken($validationResult);
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI service request authentication failed: %s', $exception->getMessage()),
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}
