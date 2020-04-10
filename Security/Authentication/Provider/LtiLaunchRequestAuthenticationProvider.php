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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Provider;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\LtiLaunchRequestToken;
use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidator;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LtiLaunchRequestAuthenticationProvider implements AuthenticationProviderInterface
{
    /** @var LtiLaunchRequestValidator */
    private $validator;

    public function __construct(LtiLaunchRequestValidator $validator)
    {
        $this->validator = $validator;
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof LtiLaunchRequestToken;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        try {
            return new LtiLaunchRequestToken($this->validator->validate($token->getAttribute('request')));
        } catch (\Throwable $exception) {
            throw new AuthenticationException('LTI launch request authentication failed');
        }
    }
}