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

    public function __construct(?ParserInterface $parser = null)
    {
        $this->parser = $parser ?? new Parser();
    }

    /**
     * @throws Throwable
     */
    public function handle(Throwable $exception, Request $request): Response
    {
        $idToken = $this->getTokenIdFrom($request);
        $token = $this->parser->parse($idToken);
        $payload = new LtiMessagePayload($token);

        $launchPresentation = $payload->getLaunchPresentation();

        $message = urlencode($exception->getMessage());

        if (null !== $launchPresentation && null !== $launchPresentation->getReturnUrl()) {
            $redirectUrl = sprintf(
                '%s%slti_msg=%s&lti_log=%s&lti_errormsg=%s&lti_errorlog=%s',
                $launchPresentation->getReturnUrl(),
                strpos($launchPresentation->getReturnUrl(), '?') ? '&' : '?',
                $message,
                $message,
                $message,
                $message
            );

            return new RedirectResponse($redirectUrl);
        }

        throw $exception;
    }

    private function getTokenIdFrom(Request $request): string
    {
        $idTokenFromQuery = $request->query->get('id_token');
        if (null !== $idTokenFromQuery) {
            return (string) $idTokenFromQuery;
        }

        return (string) $request->request->get('id_token');
    }
}
