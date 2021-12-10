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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Unit\Response;

use OAT\Bundle\Lti1p3Bundle\Response\ExceptionResponse;
use PHPUnit\Framework\TestCase;
use Exception;

final class ExceptionResponseTest extends TestCase
{
    /**
     * @dataProvider providerXssAttack
     */
    public function testXssAttackWhenCreateResponse(string $expected, string $message): void
    {
        $response = new ExceptionResponse(new Exception($message));

        self::assertSame($expected, $response->getContent());
    }

    public function providerXssAttack(): array
    {
        return [
            'On Error' => [
                '&lt;img+src=https://localhost+onerror=alert(1)&gt;',
                '<img+src=https://localhost+onerror=alert(1)>'
            ],
            'Mouse Over' => [
                '&lt;b onmouseover=alert(&#039;Wufff!&#039;)&gt;click me!&lt;/b&gt;',
                '<b onmouseover=alert(\'Wufff!\')>click me!</b>'
            ],
            'URI Schemes' => [
                '&lt;IMG SRC=j&amp;#X41vascript:alert(&#039;test2&#039;)&gt;',
                '<IMG SRC=j&#X41vascript:alert(\'test2\')>'
            ],
            'Base64' => [
                '&lt;img onload=&quot;eval(atob(&#039;d2luZG93LmFsZXJ0KCcxJyk=&#039;))&quot;&gt;',
                '<img onload="eval(atob(\'d2luZG93LmFsZXJ0KCcxJyk=\'))">'
            ]
        ];
    }
}
