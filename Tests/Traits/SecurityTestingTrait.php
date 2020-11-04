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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Traits;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;

trait SecurityTestingTrait
{
    private function createTestClientAssertion(RegistrationInterface $registration, array $scopes = []): string
    {
        $now = Carbon::now();

        return (new Builder())
            ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
            ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getPreciseTimestamp()))
            ->issuedBy($registration->getTool()->getAudience())
            ->relatedTo($registration->getClientId())
            ->permittedFor($registration->getPlatform()->getAudience())
            ->issuedAt($now->getTimestamp())
            ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
            ->getToken(new Sha256(), $registration->getToolKeyChain()->getPrivateKey())
            ->__toString();
    }
}
