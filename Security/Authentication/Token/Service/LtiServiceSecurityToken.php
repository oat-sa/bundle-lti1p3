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

namespace OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\User\User;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResultInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class LtiServiceSecurityToken extends AbstractToken
{
    /** @var string[] */
    private $roleNames;

    /** @var RequestAccessTokenValidationResultInterface|null */
    private $validationResult;

    public function __construct(?RequestAccessTokenValidationResultInterface $validationResult = null)
    {
        $this->applyValidationResult($validationResult);

        parent::__construct($this->roleNames);
    }

    public function getValidationResult(): ?RequestAccessTokenValidationResultInterface
    {
        return $this->validationResult;
    }

    public function getRegistration(): ?RegistrationInterface
    {
        return $this->validationResult
            ? $this->validationResult->getRegistration()
            : null;
    }

    public function getAccessToken(): ?TokenInterface
    {
        return $this->validationResult
            ? $this->validationResult->getToken()
            : null;
    }

    public function getScopes(): array
    {
        return $this->validationResult
            ? $this->validationResult->getScopes()
            : [];
    }

    public function getCredentials(): string
    {
        $accessToken = $this->getAccessToken();

        return $accessToken
            ? $accessToken->toString()
            : '';
    }

    public function getRoleNames(): array
    {
        return $this->roleNames;
    }

    private function applyValidationResult(?RequestAccessTokenValidationResultInterface $validationResult = null): void
    {
        $this->validationResult = $validationResult;

        if (null !== $this->validationResult) {

            $registration = $this->validationResult->getRegistration();

            if (!$this->validationResult->hasError()) {
                if (null !== $registration) {
                    $user = new User($registration->getTool()->getName());
                } else {
                    $user = new User();
                }
                $this->setUser($user);
            }

            $this->roleNames = $this->validationResult->getScopes();
        } else {
            $this->roleNames = [];
        }
    }
}
