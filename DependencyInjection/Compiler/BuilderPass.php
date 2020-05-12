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

use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Builder\KeyChainRepositoryBuilder;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Builder\RegistrationRepositoryBuilder;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Configuration;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Lti1p3Extension;
use OAT\Bundle\Lti1p3Bundle\Repository\RegistrationRepository;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configuration = $this->processConfiguration($container);

        $this
            ->defineKeyChainRepository($container, $configuration)
            ->defineRegistrationRepository($container, $configuration);
    }

    private function processConfiguration(ContainerBuilder $container): array
    {
        return (new Processor())->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig(Lti1p3Extension::ALIAS)
        );
    }

    private function defineKeyChainRepository(ContainerBuilder $container, array $configuration): self
    {
        $keyChainRepositoryDefinition = new Definition(KeyChainRepository::class);
        $keyChainRepositoryDefinition
            ->setClass(KeyChainRepository::class)
            ->setFactory([new Reference(KeyChainRepositoryBuilder::class), 'build'])
            ->setArguments([$configuration]);

        $container->setDefinition(KeyChainRepository::class, $keyChainRepositoryDefinition);

        return $this;
    }

    private function defineRegistrationRepository(ContainerBuilder $container, array $configuration): self
    {
        $registrationRepositoryDefinition = new Definition(RegistrationRepository::class);
        $registrationRepositoryDefinition
            ->setClass(RegistrationRepository::class)
            ->setFactory([new Reference(RegistrationRepositoryBuilder::class), 'build'])
            ->setArguments([$configuration]);

        $container->setDefinition(RegistrationRepository::class, $registrationRepositoryDefinition);

        return $this;
    }
}
