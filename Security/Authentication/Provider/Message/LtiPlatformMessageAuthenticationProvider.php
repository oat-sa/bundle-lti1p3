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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Provider\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiPlatformMessageSecurityToken;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

class LtiPlatformMessageAuthenticationProvider implements AuthenticationProviderInterface
{
    /** @var PlatformLaunchValidatorInterface */
    private $validator;

    /** string */
    private $firewallName;

    /** string[] */
    private $types;

    public function __construct(PlatformLaunchValidatorInterface $validator, string $firewallName, array $types = [])
    {
        $this->validator = $validator;
        $this->firewallName = $firewallName;
        $this->types = $types;
    }

    public function supports(TokenInterface $token): bool
    {
        $firewallName = $token->hasAttribute('firewall_config') ? $token->getAttribute('firewall_config')->getName() : null;

        return $token instanceof LtiPlatformMessageSecurityToken && $firewallName === $this->firewallName;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        try {
            $validationResult = $this->validator->validateToolOriginatingLaunch($token->getAttribute('request'));

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            $messageType = $validationResult->getPayload()->getMessageType();

            if (!empty($this->types) && !in_array($messageType, $this->types)) {
                throw new BadRequestHttpException(sprintf('Invalid LTI message type %s', $messageType));
            }

            return new LtiPlatformMessageSecurityToken($validationResult);
        } catch (BadRequestHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI platform message request authentication failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
