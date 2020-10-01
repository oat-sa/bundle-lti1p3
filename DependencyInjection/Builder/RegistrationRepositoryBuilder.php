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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection\Builder;

use InvalidArgumentException;
use OAT\Bundle\Lti1p3Bundle\Repository\RegistrationRepository;
use OAT\Library\Lti1p3Core\Platform\PlatformFactory;
use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationFactory;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Tool\ToolFactory;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;

class RegistrationRepositoryBuilder
{
    /** @var KeyChainFactory */
    private $keyChainFactory;

    /** @var PlatformFactory */
    private $platformFactory;

    /** @var ToolFactory */
    private $toolFactory;

    /** @var RegistrationFactory */
    private $registrationFactory;

    public function __construct(
        KeyChainFactory $keyChainFactory,
        PlatformFactory $platformFactory,
        ToolFactory $toolFactory,
        RegistrationFactory $registrationFactory
    ) {
        $this->keyChainFactory = $keyChainFactory;
        $this->platformFactory = $platformFactory;
        $this->toolFactory = $toolFactory;
        $this->registrationFactory = $registrationFactory;
    }

    public function build(array $configuration): RegistrationRepositoryInterface
    {
        return new RegistrationRepository(
            $this->buildRegistrations(
                $configuration,
                $this->buildKeyChains($configuration),
                $this->buildPlatforms($configuration),
                $this->buildTools($configuration)
            )
        );
    }

    /**
     * @return KeyChainInterface[]
     */
    private function buildKeyChains(array $configuration): array
    {
        $keyChains = [];

        foreach ($configuration['key_chains'] ?? [] as $keyId => $keyData) {
            $keyChain = $this->keyChainFactory->create(
                $keyId,
                $keyData['key_set_name'],
                $keyData['public_key'],
                $keyData['private_key'],
                $keyData['private_key_passphrase']
            );

            $keyChains[$keyId] = $keyChain;
        }

        return $keyChains;
    }

    /**
     * @return PlatformInterface[]
     */
    private function buildPlatforms(array $configuration): array
    {
        $platforms = [];

        foreach ($configuration['platforms'] ?? [] as $platformId => $platformData) {
            $platform = $this->platformFactory->create(
                $platformId,
                $platformData['name'],
                $platformData['audience'],
                $platformData['oidc_authentication_url'] ?? null,
                $platformData['oauth2_access_token_url'] ?? null
            );

            $platforms[$platformId] = $platform;
        }

        return $platforms;
    }

    /**
     * @return ToolInterface[]
     */
    private function buildTools(array $configuration): array
    {
        $tools = [];

        foreach ($configuration['tools'] as $toolId => $toolData) {
            $tool = $this->toolFactory->create(
                $toolId,
                $toolData['name'],
                $toolData['audience'],
                $toolData['oidc_initiation_url'],
                $toolData['launch_url'] ?? null,
                $toolData['deep_linking_url'] ?? null
            );

            $tools[$toolId] = $tool;
        }

        return $tools;
    }

    /**
     * @return RegistrationInterface[]
     */
    private function buildRegistrations(array $configuration, array $keyChains, array $platforms, array $tools): array
    {
        $registrations = [];

        foreach ($configuration['registrations'] as $registrationId => $registrationData) {
            if (!array_key_exists($registrationData['platform'], $platforms)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Platform %s is not defined, possible values: %s',
                        $registrationData['platform'],
                        implode(', ', array_keys($platforms))
                    )
                );
            }

            if (!array_key_exists($registrationData['tool'], $tools)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Tool %s is not defined, possible values: %s',
                        $registrationData['tool'],
                        implode(', ', array_keys($tools))
                    )
                );
            }

            if (
                isset($registrationData['platform_key_chain'])
                && !array_key_exists($registrationData['platform_key_chain'], $keyChains)
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Platform key chain %s is not defined, possible values: %s',
                        $registrationData['platform_key_chain'],
                        implode(', ', array_keys($keyChains))
                    )
                );
            }

            if (
                isset($registrationData['tool_key_chain'])
                && !array_key_exists($registrationData['tool_key_chain'], $keyChains)
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Tool key chain %s is not defined, possible values: %s',
                        $registrationData['tool_key_chain'],
                        implode(', ', array_keys($keyChains))
                    )
                );
            }

            $registration = $this->registrationFactory->create(
                $registrationId,
                $registrationData['client_id'],
                $platforms[$registrationData['platform']],
                $tools[$registrationData['tool']],
                $registrationData['deployment_ids'] ?? [],
                $keyChains[$registrationData['platform_key_chain']] ?? null,
                $keyChains[$registrationData['tool_key_chain']] ?? null,
                $registrationData['platform_jwks_url'] ?? null,
                $registrationData['tool_jwks_url'] ?? null
            );

            $registrations[$registrationId] = $registration;
        }

        return $registrations;
    }
}
