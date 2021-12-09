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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Unit\Action\Tool\Message;

use OAT\Bundle\Lti1p3Bundle\Action\Tool\Message\OidcInitiationAction;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class OidcInitiationActionTest extends TestCase
{
    /**
     * @var HttpMessageFactoryInterface
     */
    private $factory;

    /**
     * @var OidcInitiator
     */
    private $initiator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var OidcInitiationAction
     */
    private $oidcInitiationAction;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(HttpMessageFactoryInterface::class);
        $this->initiator = $this->createMock(OidcInitiator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->oidcInitiationAction = new OidcInitiationAction(
            $this->factory,
            $this->initiator,
            $this->logger
        );
    }

    /**
     * @dataProvider providerXssAttack
     */
    public function testXssAttackWhenExceptionThrown(string $expected, string $message): void
    {
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $this->factory->method('createRequest')->willReturn($serverRequest);

        $this->initiator->method('initiate')->willThrowException(new LtiException($message));

        $response = $this->oidcInitiationAction->__invoke($this->request);

        self::assertSame($expected, $response->getContent());
    }

    public function providerXssAttack(): array
    {
        return [
            'On Error' => [
                '&lt;img+src=https://ce.pentestpeople.com+onerror=alert(1)&gt;',
                '<img+src=https://ce.pentestpeople.com+onerror=alert(1)>'
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
