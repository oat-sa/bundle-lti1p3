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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Response;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ExceptionResponse extends Response
{
    public function __construct(Throwable $exception, int $status = self::HTTP_INTERNAL_SERVER_ERROR, array $headers = [])
    {
        $contentFromException = htmlspecialchars($exception->getMessage(), ENT_QUOTES);

        parent::__construct(
            $contentFromException,
            $status,
            $headers
        );
    }
}
