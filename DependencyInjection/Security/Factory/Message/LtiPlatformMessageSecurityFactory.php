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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection\Security\Factory\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Firewall\Message\LtiPlatformMessageAuthenticator;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LtiPlatformMessageSecurityFactory implements AuthenticatorFactoryInterface
{
    public const PRIORITY = -10;

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getKey(): string
    {
        return 'lti1p3_message_platform';
    }
    /**
     * Creates the authenticator service(s) for the provided configuration.
     *
     * @param array<string, mixed> $config
     *
     * @return string|string[] The authenticator service ID(s) to be used by the firewall
     */
    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): array|string {
        $authenticatorId = sprintf('security.authenticator.%s.%s', $this->getKey(), $firewallName);
        $authenticatorDefinition = new Definition(LtiPlatformMessageAuthenticator::class);
        $authenticatorDefinition
            ->setShared(false)
            ->setArguments(
                [
                    new Reference('security.firewall.map'),
                    new Reference(HttpMessageFactoryInterface::class),
                    new Reference(PlatformLaunchValidatorInterface::class),
                    $firewallName,
                    $config['types'] ?? []
                ]
            );
        $container->setDefinition($authenticatorId, $authenticatorDefinition);

        return $authenticatorId;
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node->children()->arrayNode('types')->scalarPrototype()->end();
    }
}
