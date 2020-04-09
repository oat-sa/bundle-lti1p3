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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Integration\Registration;

use OAT\Bundle\Lti1p3Bundle\Tests\Resources\Kernel\Lti1p3TestKernel;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @see Lti1p3TestKernel */
class RegistrationRepositoryTest extends KernelTestCase
{
    /** @var RegistrationRepositoryInterface */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->subject = static::$container->get(RegistrationRepositoryInterface::class);
    }

    public function testFind(): void
    {
        $result = $this->subject->find('testRegistration');

        $this->assertInstanceOf(RegistrationInterface::class, $result);
        $this->assertEquals('testRegistration', $result->getIdentifier());

        $this->assertNull($this->subject->find('invalid'));
    }

    public function testFindByPlatformIssuer(): void
    {
        $result = $this->subject->findByPlatformIssuer('http://platform.com', 'client_id');

        $this->assertInstanceOf(RegistrationInterface::class, $result);
        $this->assertEquals('testRegistration', $result->getIdentifier());
        $this->assertEquals('http://platform.com', $result->getPlatform()->getAudience());

        $this->assertEquals($result, $this->subject->findByPlatformIssuer('http://platform.com'));

        $this->assertNull($this->subject->findByPlatformIssuer('invalid'));
    }

    public function testFindByToolIssuer(): void
    {
        $result = $this->subject->findByToolIssuer('http://tool.com', 'client_id');

        $this->assertInstanceOf(RegistrationInterface::class, $result);
        $this->assertEquals('testRegistration', $result->getIdentifier());
        $this->assertEquals('http://tool.com', $result->getTool()->getAudience());

        $this->assertEquals($result, $this->subject->findByToolIssuer('http://tool.com'));

        $this->assertNull($this->subject->findByToolIssuer('invalid'));
    }
}
