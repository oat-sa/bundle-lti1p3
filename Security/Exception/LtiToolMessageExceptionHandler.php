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

namespace OAT\Bundle\Lti1p3Bundle\Security\Exception;

use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\ParserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LtiToolMessageExceptionHandler implements LtiToolMessageExceptionHandlerInterface
{
    /** @var ParserInterface */
    private $parser;

    public function __construct(ParserInterface $parser = null)
    {
        $this->parser = $parser ?? new Parser();
    }

    /**
     * @throws Throwable
     */
    public function handle(Throwable $exception, Request $request): Response
    {
        $payload = new LtiMessagePayload($this->parser->parse($request->get('id_token')));

        if (null !== $payload->getLaunchPresentation() && null !== $payload->getLaunchPresentation()->getReturnUrl()) {
            $redirectUrl = sprintf(
                '%s%slti_errormsg=%s',
                $payload->getLaunchPresentation()->getReturnUrl(),
                strpos($payload->getLaunchPresentation()->getReturnUrl(), '?') ? '&' : '?',
                urlencode($exception->getMessage())
            );

            return new RedirectResponse($redirectUrl);
        }

        throw $exception;
    }
}
