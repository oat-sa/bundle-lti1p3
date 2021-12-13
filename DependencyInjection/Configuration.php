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

use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(Lti1p3Extension::ALIAS);

        /** @var ArrayNodeDefinition $rootNode */
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
            ->scalarPrototype();

        return $this;
    }

    public function addKeyChainsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $keyChainsIdentifier = $rootNode
            ->fixXmlConfig('key_chain')
            ->children()
            ->arrayNode('key_chains')
            ->useAttributeAsKey('identifier')
            ->arrayPrototype()
            ->children();

        $keyChainsIdentifier->scalarNode('key_set_name')->isRequired();
        $keyChainsIdentifier->scalarNode('public_key')->isRequired();
        $keyChainsIdentifier->scalarNode('private_key')->defaultNull();
        $keyChainsIdentifier->scalarNode('private_key_passphrase')->defaultNull();
        $keyChainsIdentifier->scalarNode('algorithm')->defaultValue(KeyInterface::ALG_RS256);

        return $this;
    }

    public function addPlatformsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $platformsIdentifier = $rootNode
            ->fixXmlConfig('platform')
            ->children()
            ->arrayNode('platforms')
            ->useAttributeAsKey('identifier')
            ->arrayPrototype()
            ->children();

        $platformsIdentifier->scalarNode('name')->isRequired();
        $platformsIdentifier->scalarNode('audience')->isRequired();
        $platformsIdentifier->scalarNode('oidc_authentication_url')->defaultNull();
        $platformsIdentifier->scalarNode('oauth2_access_token_url')->defaultNull();

        return $this;
    }

    public function addToolsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $toolsIdentifier = $rootNode
            ->fixXmlConfig('tool')
            ->children()
            ->arrayNode('tools')
            ->useAttributeAsKey('identifier')
            ->arrayPrototype()
            ->children();

        $toolsIdentifier->scalarNode('name')->isRequired();
        $toolsIdentifier->scalarNode('audience')->isRequired();
        $toolsIdentifier->scalarNode('oidc_initiation_url')->isRequired();
        $toolsIdentifier->scalarNode('launch_url')->defaultNull();
        $toolsIdentifier->scalarNode('deep_linking_url')->defaultNull();

        return $this;
    }

    public function addRegistrationsConfiguration(ArrayNodeDefinition $rootNode): self
    {
        $registrationsIdentifier = $rootNode
            ->fixXmlConfig('registration')
            ->children()
            ->arrayNode('registrations')
            ->useAttributeAsKey('identifier')
            ->arrayPrototype()
            ->children();

        $registrationsIdentifier->scalarNode('client_id')->isRequired();
        $registrationsIdentifier->scalarNode('platform')->isRequired();
        $registrationsIdentifier->scalarNode('tool')->isRequired();
        $registrationsIdentifier->arrayNode('deployment_ids')->scalarPrototype();
        $registrationsIdentifier->scalarNode('platform_key_chain')->defaultNull();
        $registrationsIdentifier->scalarNode('tool_key_chain')->defaultNull();
        $registrationsIdentifier->scalarNode('platform_jwks_url')->defaultNull();
        $registrationsIdentifier->scalarNode('tool_jwks_url')->defaultNull();
        $registrationsIdentifier->integerNode('order')->defaultNull();

        return $this;
    }
}
