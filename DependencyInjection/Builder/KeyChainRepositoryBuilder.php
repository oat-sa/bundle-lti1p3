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

namespace OAT\Bundle\Lti1p3Bundle\DependencyInjection\Builder;

use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

class KeyChainRepositoryBuilder
{
    /** @var KeyChainFactoryInterface */
    private $factory;

    public function __construct(KeyChainFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function build(array $configuration): KeyChainRepositoryInterface
    {
        $repository = new KeyChainRepository();

        foreach ($configuration['key_chains'] ?? [] as $keyId => $keyData) {
            $repository->addKeyChain(
                $this->factory->create(
                    $keyId,
                    $keyData['key_set_name'],
                    $keyData['public_key'],
                    $keyData['private_key'] ?? null,
                    $keyData['private_key_passphrase'] ?? null,
                    $keyData['algorithm'] ?? KeyInterface::ALG_RS256
                )
            );
        }

        return $repository;
    }
}
