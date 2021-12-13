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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Resources\Kernel;

use OAT\Bundle\Lti1p3Bundle\Lti1p3Bundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Lti1p3TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundles = [
            FrameworkBundle::class,
            SecurityBundle::class,
            Lti1p3Bundle::class
        ];

        foreach ($bundles as $class) {
            yield new $class();
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // bundle jwks route
        $routes->import(__DIR__  . '/../../../Resources/config/routing/jwks.yaml');

        // bundle message routes
        $routes->import(__DIR__ . '/../../../Resources/config/routing/message/platform.yaml');
        $routes->import(__DIR__ . '/../../../Resources/config/routing/message/tool.yaml');

        // bundle service route
        $routes->import(__DIR__ . '/../../../Resources/config/routing/service/platform.yaml');

        //testing routes
        $routes->import(__DIR__  . '/config/routes.yaml');
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        // bundle config
        $loader->load(__DIR__ . '/../../../Resources/config/services.yaml');

        // testing config
        $loader->load(__DIR__ . '/config/config.yaml');
        $loader->load(__DIR__ . '/config/security.yaml');
        $loader->load(__DIR__ . '/config/lti1p3.yaml');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->environment . '/cache/' . spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->environment . '/logs/' . spl_object_hash($this);
    }
}
