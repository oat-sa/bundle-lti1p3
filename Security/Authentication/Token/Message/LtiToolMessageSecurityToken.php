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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\User\User;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResultInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;

class LtiToolMessageSecurityToken extends AbstractLtiMessageSecurityToken
{
    public function getState(): ?MessagePayloadInterface
    {
        return $this->validationResult?->getState();
    }

    protected function applyValidationResult(?LaunchValidationResultInterface $validationResult = null): void
    {
        $this->validationResult = $validationResult;

        if (null !== $this->validationResult) {
            $payload = $this->validationResult->getPayload();

            if (null !== $payload && !$this->validationResult->hasError()) {
                $userIdentity = $payload->getUserIdentity();

                if (null !== $userIdentity) {
                    $user = new User($userIdentity->getIdentifier());
                    $this->setUser($user);
                }

                $this->roleNames = $payload->getRoles();
            }
        } else {
            $this->roleNames = [];
        }
    }
}
