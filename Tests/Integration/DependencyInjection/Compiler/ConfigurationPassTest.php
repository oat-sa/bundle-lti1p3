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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Integration\DependencyInjection\Compiler;

use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAT\Bundle\Lti1p3Bundle\Tests\Resources\Kernel\Lti1p3TestKernel;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @see Lti1p3TestKernel */
class ConfigurationPassTest extends KernelTestCase
{
    /** @var ScopeRepositoryInterface */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::getContainer()->get(ScopeRepositoryInterface::class);
    }

    public function testConfiguredScopesAreAvailableInScopeRepositoryInstance(): void
    {
        $this->assertInstanceOf(Scope::class, $this->repository->getScopeEntityByIdentifier('allowed-scope'));
        $this->assertEquals('allowed-scope', $this->repository->getScopeEntityByIdentifier('allowed-scope')->getIdentifier());

        $this->assertNull($this->repository->getScopeEntityByIdentifier('invalid'));
    }
}
