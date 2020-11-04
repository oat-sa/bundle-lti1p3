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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(Lti1p3Extension::ALIAS);

        $rootNode = $treeBuilder->getRootNode();

        $this
            ->addScopesConfiguration($rootNode)
            ->addKeyChainsConfiguration($rootNode)
            ->addPlatformsConfiguration($rootNode)
            ->addToolsConfiguration($rootNode)
            ->addRegistrationsConfiguration($rootNode);

        return $treeBuilder;
    }

    public function addScopesConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $rootNode
            ->fixXmlConfig('scope')
                ->children()
                    ->arrayNode('scopes')
                        ->scalarPrototype()
                    ->end()
                ->end()
            ->end();

        return $this;
    }

    public function addKeyChainsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $rootNode
            ->fixXmlConfig('key_chain')
                ->children()
                    ->arrayNode('key_chains')
                        ->useAttributeAsKey('identifier')
                        ->arrayPrototype()
                        ->children()
                            ->scalarNode('key_set_name')->isRequired()->end()
                            ->scalarNode('public_key')->isRequired()->end()
                            ->scalarNode('private_key')->end()
                            ->scalarNode('private_key_passphrase')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $this;
    }

    public function addPlatformsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $rootNode
            ->fixXmlConfig('platform')
                ->children()
                ->arrayNode('platforms')
                    ->useAttributeAsKey('identifier')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('audience')->isRequired()->end()
                            ->scalarNode('oidc_authentication_url')->end()
                            ->scalarNode('oauth2_access_token_url')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $this;
    }

    public function addToolsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $rootNode
            ->fixXmlConfig('tool')
                ->children()
                    ->arrayNode('tools')
                    ->useAttributeAsKey('identifier')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('audience')->isRequired()->end()
                            ->scalarNode('oidc_initiation_url')->isRequired()->end()
                            ->scalarNode('launch_url')->end()
                            ->scalarNode('deep_linking_url')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $this;
    }

    public function addRegistrationsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $rootNode
            ->fixXmlConfig('registration')
                ->children()
                    ->arrayNode('registrations')
                    ->useAttributeAsKey('identifier')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('client_id')->isRequired()->end()
                            ->scalarNode('platform')->isRequired()->end()
                            ->scalarNode('tool')->isRequired()->end()
                            ->arrayNode('deployment_ids')->scalarPrototype()->end()->end()
                            ->scalarNode('platform_key_chain')->end()
                            ->scalarNode('tool_key_chain')->end()
                            ->scalarNode('platform_jwks_url')->end()
                            ->scalarNode('tool_jwks_url')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $this;
    }
}
