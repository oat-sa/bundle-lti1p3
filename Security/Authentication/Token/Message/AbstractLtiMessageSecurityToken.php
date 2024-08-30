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

use OAT\Library\Lti1p3Core\Message\Launch\Validator\Result\LaunchValidationResultInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

abstract class AbstractLtiMessageSecurityToken extends AbstractToken
{
    /** @var string[] */
    protected array $roleNames = [];

    /** @var LaunchValidationResultInterface|null */
    protected $validationResult;

    public function __construct(?LaunchValidationResultInterface $validationResult = null)
    {
        $this->applyValidationResult($validationResult);

        parent::__construct($this->roleNames);
    }

    public function getValidationResult(): ?LaunchValidationResultInterface
    {
        return $this->validationResult;
    }

    public function getRegistration(): ?RegistrationInterface
    {
        return $this->validationResult?->getRegistration();
    }

    public function getPayload(): ?LtiMessagePayloadInterface
    {
        return $this->validationResult?->getPayload();
    }

    public function getCredentials(): string
    {
        $payload = $this->getPayload();

        return $payload?->getToken()->toString();
    }

    public function getRoleNames(): array
    {
        return $this->roleNames;
    }

    abstract protected function applyValidationResult(?LaunchValidationResultInterface $validationResult = null): void;
}
