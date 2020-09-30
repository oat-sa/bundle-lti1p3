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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Resources\Action\Platform\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiPlatformMessageSecurityToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

class TestPlatformMessageAction
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(): JsonResponse
    {
        /** @var LtiPlatformMessageSecurityToken $token */
        $token = $this->security->getToken();

        return new JsonResponse([
            'claims' => [
                'type' => $token->getPayload()->getMessageType(),
                'custom' => $token->getPayload()->getClaim('custom'),
            ],
            'validations' => [
                'successes' => $token->getValidationResult()->getSuccesses(),
                'error' => $token->getValidationResult()->getError(),
            ],
            'registration' => $token->getRegistration()->getIdentifier(),
            'credentials' => $token->getCredentials()
        ]);
    }
}
