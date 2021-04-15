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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Resources\Service\Server\Handler;

use Exception;
use Http\Message\ResponseFactory;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\Result\RequestAccessTokenValidationResultInterface;
use OAT\Library\Lti1p3Core\Service\Server\Handler\LtiServiceServerRequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Security;
use Throwable;

class TestServiceRequestHandler implements LtiServiceServerRequestHandlerInterface
{
    /** @var Security */
    private $security;

    /** @var ResponseFactory */
    private $factory;

    public function __construct(Security $security, ResponseFactory $factory)
    {
        $this->security = $security;
        $this->factory = $factory;
    }

    public function getServiceName(): string
    {
        return 'test-service';
    }

    public function getAllowedContentType(): ?string
    {
        return 'application/json';
    }

    public function getAllowedMethods(): array
    {
        return [
            'GET',
        ];
    }

    public function getAllowedScopes(): array
    {
        return [
            'allowed-scope',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handleValidatedServiceRequest(
        RequestAccessTokenValidationResultInterface $validationResult,
        ServerRequestInterface $request,
        array $options = []
    ): ResponseInterface {

        $shouldThrowException = $options['shouldThrowException'] ?? null;

        if (null !== $shouldThrowException) {
            throw new Exception('handler generic error');
        }

        $registration = $validationResult->getRegistration();

        /** @var LtiServiceSecurityToken $token */
        $token = $this->security->getToken();

        $body = json_encode(
            [
                'claims' => $token->getAccessToken()->getClaims()->all(),
                'roles' => $token->getRoleNames(),
                'validations' => [
                    'successes' => $token->getValidationResult()->getSuccesses(),
                    'error' => $token->getValidationResult()->getError(),
                ],
                'registration' => $registration->getIdentifier(),
                'token_registration' => $token->getRegistration()->getIdentifier(),
                'scopes' => $token->getScopes(),
                'credentials' => $token->getCredentials()
            ]
        );

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($body),
        ];

        return $this->factory->createResponse(200, null, $headers, $body);
    }
}
