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

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidatorInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

class LtiToolMessageAuthenticationProvider implements AuthenticationProviderInterface
{
    /** @var ToolLaunchValidatorInterface */
    private $validator;

    /** string[] */
    private $types;

    public function __construct(ToolLaunchValidatorInterface $validator, array $types = [])
    {
        $this->validator = $validator;
        $this->types = $types;
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof LtiToolMessageSecurityToken;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        try {
            $validationResult = $this->validator->validatePlatformOriginatingLaunch($token->getAttribute('request'));

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            $messageType = $validationResult->getPayload()->getMessageType();

            if (!empty($this->types) && !in_array($messageType, $this->types)) {
                throw new BadRequestException(sprintf('Invalid LTI message type %s', $messageType));
            }

            return new LtiToolMessageSecurityToken($validationResult);
        } catch (BadRequestException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI tool message request authentication failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
