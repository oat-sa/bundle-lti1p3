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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection\Security\Factory\Service;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Provider\Service\LtiServiceAuthenticationProvider;
use OAT\Bundle\Lti1p3Bundle\Security\Firewall\Service\LtiServiceAuthenticationListener;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidatorInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\RemoteUserFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LtiServiceSecurityFactory implements AuthenticatorFactoryInterface
{
    public function getPriority(): string
    {
        return RemoteUserFactory::PRIORITY;
    }

    public function getKey(): string
    {
        return 'lti1p3_service';
    }

    public function create(
        ContainerBuilder $container,
        $id,
        $config,
        $userProvider,
        $defaultEntryPoint = null
    ) {
        $providerId = sprintf('security.authentication.provider.%s.%s', $this->getKey(), $id);
        $providerDefinition = new Definition(LtiServiceAuthenticationProvider::class);
        $providerDefinition
            ->setShared(false)
            ->setArguments(
                [
                    new Reference(RequestAccessTokenValidatorInterface::class),
                    $id,
                    $config['scopes'] ?? []
                ]
            );
        $container->setDefinition($providerId, $providerDefinition);

        $listenerId = sprintf('security.authentication.listener.%s.%s', $this->getKey(), $id);
        $container->setDefinition($listenerId, new ChildDefinition(LtiServiceAuthenticationListener::class));

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node->children()->arrayNode('scopes')->scalarPrototype()->end();
    }
}
