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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Integration\DependencyInjection\Builder;

use OAT\Bundle\Lti1p3Bundle\Tests\Resources\Kernel\Lti1p3TestKernel;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @see Lti1p3TestKernel */
class KeyChainRepositoryBuilderTest extends KernelTestCase
{
    /** @var KeyChainRepositoryInterface */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::getContainer()->get(KeyChainRepositoryInterface::class);
    }

    public function testBuildRepositoryClass(): void
    {
        $this->assertInstanceOf(KeyChainRepository::class, $this->repository);
    }

    public function testBuildRepositoryCanFindKeySet(): void
    {
        $result = $this->repository->find('kid1');

        $this->assertInstanceOf(KeyChainInterface::class, $result);
        $this->assertEquals('kid1', $result->getIdentifier());

        $this->assertNull($this->repository->find('invalid'));
    }

    public function testBuildRepositoryCanFindKeySetByKeySetName(): void
    {
        $result = $this->repository->findByKeySetName('toolSet');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(KeyChainInterface::class, current($result));
        $this->assertEquals('kid2', current($result)->getIdentifier());

        $this->assertEmpty($this->repository->findByKeySetName('invalid'));
    }
}
