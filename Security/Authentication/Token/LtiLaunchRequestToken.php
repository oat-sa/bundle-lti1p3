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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token;

use OAT\Library\Lti1p3Core\Launch\Validator\LtiLaunchRequestValidationResult;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class LtiLaunchRequestToken extends AbstractToken
{
    /** @var string[] */
    private $roleNames;

    /** @var LtiLaunchRequestValidationResult|null */
    private $validationResult;

    public function __construct(LtiLaunchRequestValidationResult $validationResult = null)
    {
        $this->applyValidationResult($validationResult);

        parent::__construct($this->roleNames);
    }

    public function getValidationResult(): ?LtiLaunchRequestValidationResult
    {
        return $this->validationResult;
    }

    public function getLtiMessage(): ?LtiMessageInterface
    {
        return $this->validationResult
            ? $this->validationResult->getLtiMessage()
            : null;
    }

    public function getCredentials(): string
    {
        return '';
    }

    public function getRoleNames(): array
    {
        return $this->roleNames;
    }

    private function applyValidationResult(LtiLaunchRequestValidationResult $validationResult = null): void
    {
        $this->validationResult = $validationResult;

        if (null !== $this->validationResult) {
            $userIdentity = $validationResult->getLtiMessage()->getUserIdentity();

            if (null !== $userIdentity) {
                $this->setUser($userIdentity->getIdentifier());
            }

            $this->roleNames = $validationResult->getLtiMessage()->getRoles();

            $this->setAuthenticated(!$this->validationResult->hasFailures());
        } else {
            $this->roleNames = [];

            $this->setAuthenticated(false);
        }
    }
}
