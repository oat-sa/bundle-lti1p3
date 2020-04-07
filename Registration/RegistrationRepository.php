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

namespace OAT\Bundle\Lti1p3Bundle\Registration;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

class RegistrationRepository implements RegistrationRepositoryInterface
{
    /** @var RegistrationInterface[] */
    private $registrations;

    /** @param RegistrationInterface[] $registrations */
    public function __construct(array $registrations)
    {
        foreach ($registrations as $registration) {
            $this->registrations[$registration->getIdentifier()] = $registration;
        }
    }

    public function find(string $identifier): ?RegistrationInterface
    {
        return $this->registrations[$identifier] ?? null;
    }

    public function findByPlatformIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
    {
        foreach ($this->registrations as $registration) {
            if ($registration->getPlatform()->getAudience() === $issuer) {
                if (null !== $clientId) {
                    if ($registration->getClientId() === $clientId) {
                        return $registration;
                    }
                } else {
                    return $registration;
                }
            }
        }

        return null;
    }

    public function findByToolIssuer(string $issuer, string $clientId = null): ?RegistrationInterface
    {
        foreach ($this->registrations as $registration) {
            if ($registration->getTool()->getAudience() === $issuer) {
                if (null !== $clientId) {
                    if ($registration->getClientId() === $clientId) {
                        return $registration;
                    }
                } else {
                    return $registration;
                }
            }
        }

        return null;
    }
}
