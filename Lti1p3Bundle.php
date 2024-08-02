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

namespace OAT\Bundle\Lti1p3Bundle;

use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Compiler\BuilderPass;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Compiler\ConfigurationPass;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Security\Factory\Message\LtiPlatformMessageSecurityFactory;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Security\Factory\Message\LtiToolMessageSecurityFactory;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Security\Factory\Service\LtiServiceSecurityFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;

class Lti1p3Bundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Bundle dependencies builder pass
        $container->addCompilerPass(new BuilderPass());

        // Bundle configuration pass
        $container->addCompilerPass(new ConfigurationPass());

        // Bundle security extension configuration
        $this->configureSecurityExtension($container->getExtension('security'));
    }

    private function configureSecurityExtension(AuthenticatorFactoryInterface $securityExtension): void
    {
        // LTI platform messages security registration
        $securityExtension->addAuthenticatorFactory(new LtiPlatformMessageSecurityFactory());

        // LTI tool messages security registration
        $securityExtension->addAuthenticatorFactory(new LtiToolMessageSecurityFactory());

        // LTI services security registration
        $securityExtension->addAuthenticatorFactory(new LtiServiceSecurityFactory());
    }
}
