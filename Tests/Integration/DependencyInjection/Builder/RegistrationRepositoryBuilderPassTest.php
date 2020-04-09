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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Integration\DependencyInjection\Builder;

use InvalidArgumentException;
use OAT\Bundle\Lti1p3Bundle\Registration\RegistrationRepository;
use OAT\Bundle\Lti1p3Bundle\Tests\Resources\Kernel\Lti1p3TestKernel;
use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @see Lti1p3TestKernel */
class RegistrationRepositoryBuilderPassTest extends KernelTestCase
{
    public function tearDown(): void
    {
        // ensure next tests will execute with correct ENV conditions
        putenv('LTI_CONFIG_FILE=lti1p3.yaml');
    }

    public function testBuildFailsOnInvalidPlatform(): void
    {
        putenv('LTI_CONFIG_FILE=lti/invalidPlatform.yaml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform invalid is not defined, possible values: testPlatform');

        static::bootKernel();

        static::$container->get(RegistrationRepositoryInterface::class);
    }

    public function testBuildFailsOnInvalidTool(): void
    {
        putenv('LTI_CONFIG_FILE=lti/invalidTool.yaml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tool invalid is not defined, possible values: testTool');

        static::bootKernel();

        static::$container->get(RegistrationRepositoryInterface::class);
    }

    public function testBuildFailsOnInvalidPlatformKeyChain(): void
    {
        putenv('LTI_CONFIG_FILE=lti/invalidPlatformKeyChain.yaml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform key chain invalid is not defined, possible values: kid1, kid2');

        static::bootKernel();

        static::$container->get(RegistrationRepositoryInterface::class);
    }

    public function testBuildFailsOnInvalidToolKeyChain(): void
    {
        putenv('LTI_CONFIG_FILE=lti/invalidToolKeyChain.yaml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tool key chain invalid is not defined, possible values: kid1, kid2');

        static::bootKernel();

        static::$container->get(RegistrationRepositoryInterface::class);
    }

    public function testBuildRepositoryCanFind(): void
    {
        putenv('LTI_CONFIG_FILE=lti1p3.yaml');

        static::bootKernel();

        $repository = static::$container->get(RegistrationRepositoryInterface::class);

        $this->assertInstanceOf(RegistrationRepository::class, $repository);

        $registration = $repository->find('testRegistration');
        $this->assertInstanceOf(RegistrationInterface::class, $registration);
        $this->assertEquals('testRegistration', $registration->getIdentifier());
        $this->assertEquals('client_id', $registration->getClientId());
        $this->assertInstanceOf(PlatformInterface::class, $registration->getPlatform());
        $this->assertInstanceOf(ToolInterface::class, $registration->getTool());
    }
}
