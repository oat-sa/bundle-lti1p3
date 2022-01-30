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

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Provider\Message\LtiPlatformMessageAuthenticationProvider;
use OAT\Bundle\Lti1p3Bundle\Security\Authenticator\LtiPlatformMessageAuthenticator;
use OAT\Bundle\Lti1p3Bundle\Security\Firewall\Message\LtiPlatformMessageAuthenticationListener;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\FirewallMapInterface;

class LtiPlatformMessageSecurityFactory implements SecurityFactoryInterface, AuthenticatorFactoryInterface
{
    /**
     * @deprecated since 6.1.1
     */
    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'lti1p3_message_platform';
    }

    public function getPriority(): int
    {
        return 0;
    }

    /**
     * @deprecated since 6.1.1
     */
    public function create(
        ContainerBuilder $container,
        $id,
        $config,
        $userProvider,
        $defaultEntryPoint = null
    ) {
        $providerId = sprintf('security.authentication.provider.%s.%s', $this->getKey(), $id);
        $providerDefinition = new Definition(LtiPlatformMessageAuthenticationProvider::class);
        $providerDefinition
            ->setShared(false)
            ->setArguments(
                [
                    new Reference(PlatformLaunchValidatorInterface::class),
                    $id,
                    $config['types'] ?? []
                ]
            );
        $container->setDefinition($providerId, $providerDefinition);

        $listenerId = sprintf('security.authentication.listener.%s.%s', $this->getKey(), $id);
        $container->setDefinition($listenerId, new ChildDefinition(LtiPlatformMessageAuthenticationListener::class));

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node->children()->arrayNode('types')->scalarPrototype()->end();
    }

    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ) {
        $authenticatorId = sprintf('security.authenticator.%s.%s', $this->getKey(), $firewallName);

        $authenticatorDefinition = new Definition(LtiPlatformMessageAuthenticator::class);
        $authenticatorDefinition
            ->setShared(false)
            ->setArguments(
                [
                    new Reference(HttpMessageFactoryInterface::class),
                    new Reference(FirewallMapInterface::class),
                    new Reference(PlatformLaunchValidatorInterface::class),
                    $firewallName,
                    $config['types'] ?? []
                ]
            );
        $container->setDefinition($authenticatorId, $authenticatorDefinition);

        return $authenticatorId;
    }
}
