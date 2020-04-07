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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Configuration;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Lti1p3Extension;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Tool\Tool;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DependencyBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $configuration = $this->processConfiguration($container);

        // build & inject key chains definitions
        $keyChainsDefinitions = $this->buildKeyChainsDefinitions($configuration);

        $keyChainRepository = $container->getDefinition(KeyChainRepositoryInterface::class);
        $keyChainRepository->setArgument(0, $keyChainsDefinitions);

        // build & inject registrations definitions
        $registrationsDefinitions = $this->buildRegistrationsDefinitions(
            $configuration,
            $keyChainsDefinitions,
            $this->buildPlatformsDefinitions($configuration),
            $this->buildToolsDefinitions($configuration)
        );

        $registrationRepository = $container->getDefinition(RegistrationRepositoryInterface::class);
        $registrationRepository->setArgument(0, $registrationsDefinitions);
    }

    private function processConfiguration(ContainerBuilder $container): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig(Lti1p3Extension::ALIAS)
        );
    }

    /**
     * @return Definition[]
     */
    private function buildKeyChainsDefinitions(array $configuration): array
    {
        $definitions = [];

        foreach ($configuration['key_chains'] as $keyId => $keyData) {
            $definition = new Definition(KeyChain::class, [
                $keyId,
                $keyData['key_set_name'],
                $keyData['public_key'],
                $keyData['private_key'],
                $keyData['private_key_passphrase']
            ]);

            $definitions[$keyId] = $definition;
        }

        return $definitions;
    }

    /**
     * @return Definition[]
     */
    private function buildPlatformsDefinitions(array $configuration): array
    {
        $definitions = [];

        foreach ($configuration['platforms'] as $platformId => $platformData) {
            $definition = new Definition(Platform::class, [
                $platformId,
                $platformData['name'],
                $platformData['audience'],
                $platformData['oidc_authentication_url'] ?? null,
                $platformData['oauth2_access_token_url'] ?? null
            ]);

            $definitions[$platformId] = $definition;
        }

        return $definitions;
    }

    /**
     * @return Definition[]
     */
    private function buildToolsDefinitions(array $configuration): array
    {
        $definitions = [];

        foreach ($configuration['tools'] as $toolId => $toolData) {
            $definition = new Definition(Tool::class, [
                $toolId,
                $toolData['name'],
                $toolData['audience'],
                $toolData['oidc_login_initiation_url'] ?? null,
                $toolData['launch_url'] ?? null,
                $toolData['deep_link_launch_url'] ?? null
            ]);

            $definitions[$toolId] = $definition;
        }

        return $definitions;
    }

    /**
     * @return Definition[]
     */
    private function buildRegistrationsDefinitions(
        array $configuration,
        array $keyChainsDefinitions,
        array $platformsDefinitions,
        array $toolsDefinitions
    ): array {
        $definitions = [];

        foreach ($configuration['registrations'] as $registrationId => $registrationData) {

            if (!array_key_exists($registrationData['platform'], $platformsDefinitions)) {
                throw new InvalidArgumentException(sprintf(
                    'Platform %s is not defined, possible values: %s',
                    $registrationData['platform'],
                    implode(', ', array_keys($platformsDefinitions))
                ));
            }

            if (!array_key_exists($registrationData['tool'], $toolsDefinitions)) {
                throw new InvalidArgumentException(sprintf(
                    'Tool %s is not defined, possible values: %s',
                    $registrationData['tool'],
                    implode(', ', array_keys($toolsDefinitions))
                ));
            }

            if (
                isset($registrationData['platform_key_chain'])
                && !array_key_exists($registrationData['platform_key_chain'], $keyChainsDefinitions)
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Platform key chain %s is not defined, possible values: %s',
                    $registrationData['platform_key_chain'],
                    implode(', ', array_keys($keyChainsDefinitions))
                ));
            }

            if (
                isset($registrationData['tool_key_chain'])
                && !array_key_exists($registrationData['tool_key_chain'], $keyChainsDefinitions)
            ) {
                throw new InvalidArgumentException(sprintf(
                   'Tool key chain %s is not defined, possible values: %s',
                   $registrationData['tool_key_chain'],
                   implode(', ', array_keys($keyChainsDefinitions))
               ));
            }

            $definition = new Definition(Registration::class, [
                $registrationId,
                $registrationData['client_id'],
                $platformsDefinitions[$registrationData['platform']],
                $toolsDefinitions[$registrationData['tool']],
                $registrationData['deployment_ids'] ?? [],
                $keyChainsDefinitions[$registrationData['platform_key_chain']],
                $keyChainsDefinitions[$registrationData['tool_key_chain']],
                $registrationData['platform_jwks_url'] ?? null,
                $registrationData['tool_jwks_url'] ?? null
            ]);

            $definitions[$registrationId] = $definition;
        }

        return $definitions;
    }
}
