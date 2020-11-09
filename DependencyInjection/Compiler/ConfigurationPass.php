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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection\Compiler;

use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Entity\Scope;
use OAT\Library\Lti1p3Core\Service\Server\Repository\ScopeRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConfigurationPass extends AbstractPass
{
    public function process(ContainerBuilder $container): void
    {
        $configuration = $this->processConfiguration($container);

        $this->configureServiceScopeRepository($container, $configuration);
    }

    private function configureServiceScopeRepository(ContainerBuilder $container, array $configuration): self
    {
        $scopeRepository = $container->hasDefinition(ScopeRepositoryInterface::class)
            ? $container->getDefinition(ScopeRepositoryInterface::class)
            : new Definition(ScopeRepository::class);

        foreach ($configuration['scopes']  ?? [] as $scope) {
            $scopeRepository->addMethodCall('addScope', [new Definition(Scope::class, [$scope])]);
        }

        $container->setDefinition(ScopeRepositoryInterface::class, $scopeRepository);

        return $this;
    }
}
