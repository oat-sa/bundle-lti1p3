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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Provider\Service;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Service\Server\Validator\AccessTokenRequestValidator;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

class LtiServiceAuthenticationProvider implements AuthenticationProviderInterface
{
    /** @var AccessTokenRequestValidator */
    private $validator;

    /** string[] */
    private $scopes;

    public function __construct(AccessTokenRequestValidator $validator, array $scopes = [])
    {
        $this->validator = $validator;
        $this->scopes = $scopes;
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof LtiServiceSecurityToken;
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
                $exception->getCode(),
                $exception
            );
        }
    }
}
