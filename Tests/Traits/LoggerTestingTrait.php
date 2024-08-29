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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Traits;

use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

trait LoggerTestingTrait
{
    /**
     * @throws LogicException
     */
    protected function assertHasLogRecords($level): void
    {
        $this->checkLoggerTestingTraitUsage();

        $this->assertTrue(
            static::getContainer()->get(LoggerInterface::class)->hasRecords($level),
            sprintf(
                'Failed asserting that logger contains records for level %s',
                $level
            )
        );
    }

    /**
     * @throws LogicException
     */
    protected function assertHasLogRecord($record, $level): void
    {
        $this->checkLoggerTestingTraitUsage();

        $this->assertTrue(
            static::getContainer()->get(LoggerInterface::class)->hasRecord($record, $level),
            sprintf(
                'Failed asserting that logger contains record: [%s] %s',
                $level,
                is_string($record) ? $record : json_encode($record)
            )
        );
    }

    /**
     * @throws LogicException
     */
    protected function assertHasLogRecordThatContains($record, $level): void
    {
        $this->checkLoggerTestingTraitUsage();

        $this->assertTrue(
            static::getContainer()->get(LoggerInterface::class)->hasRecordThatContains($record, $level),
            sprintf(
                'Failed asserting that logger contains record containing: [%s] %s',
                $level,
                is_string($record) ? $record : json_encode($record)
            )
        );
    }

    /**
     * @throws LogicException
     */
    protected function resetTestLogger(): void
    {
        $this->checkLoggerTestingTraitUsage();

        static::getContainer()->get(LoggerInterface::class)->reset();
    }

    /**
     * @throws LogicException
     */
    private function checkLoggerTestingTraitUsage(): void
    {
        if (!is_a(static::class, KernelTestCase::class, true)) {
            throw new LogicException(
                sprintf(
                    'The %s trait must be used in tests extending %s',
                    LoggerTestingTrait::class,
                    KernelTestCase::class
                )
            );
        }
    }
}
