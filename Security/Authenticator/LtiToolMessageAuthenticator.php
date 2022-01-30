<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\Lti1p3Bundle\Security\Authenticator;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Bundle\Lti1p3Bundle\Security\Exception\LtiToolMessageExceptionHandlerInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Throwable;

class LtiToolMessageAuthenticator extends AbstractAuthenticator
{
    /** @var HttpMessageFactoryInterface */
    private $httpMessageFactory;

    /** @var FirewallMap */
    private $firewallMap;

    /** @var ToolLaunchValidatorInterface */
    private $toolLaunchValidator;

    /** @var LtiToolMessageExceptionHandlerInterface */
    private $exceptionHandler;

    /** @var string */
    private $firewallName;

    /** @var string[] */
    private $types;


    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        FirewallMap $firewallMap,
        ToolLaunchValidatorInterface $toolLaunchValidator,
        LtiToolMessageExceptionHandlerInterface $exceptionHandler,
        string $firewallName,
        array $types = []
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->firewallMap = $firewallMap;
        $this->toolLaunchValidator = $toolLaunchValidator;
        $this->exceptionHandler = $exceptionHandler;
        $this->firewallName = $firewallName;
        $this->types = $types;
    }

    public function supports(Request $request): ?bool
    {
        $firewallConfig = $this->firewallMap->getFirewallConfig($request);

        if (null === $firewallConfig) {
            return false;
        }

        return null !== $request->get('id_token') && $this->firewallName === $firewallConfig->getName();
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $validationResult = $this->toolLaunchValidator
                ->validatePlatformOriginatingLaunch($this->httpMessageFactory->createRequest($request));

            if ($validationResult->hasError()) {
                throw new LtiException($validationResult->getError());
            }

            if (null === $validationResult->getPayload()) {
                throw new LtiException('LTI Message Payload required');
            }

            $messageType = $validationResult->getPayload()->getMessageType();

            if (!empty($this->types) && !in_array($messageType, $this->types, true)) {
                throw new BadRequestHttpException(sprintf('Invalid LTI message type %s', $messageType));
            }

            $passport = new SelfValidatingPassport(new UserBadge('lti', function () {
                return new InMemoryUser('lti', null);
            }));

            $passport->setAttribute('validationResult', $validationResult);

            return $passport;
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                sprintf('LTI tool message request authentication failed: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new LtiToolMessageSecurityToken($passport->getAttribute('validationResult'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        try {
            return $this->exceptionHandler->handle($exception, $request);
        } catch (Throwable $exception) {
            $statusCode = $exception->getPrevious() instanceof BadRequestHttpException
                ? Response::HTTP_BAD_REQUEST
                : Response::HTTP_UNAUTHORIZED;

            return new Response($exception->getMessage(), $statusCode);
        }
    }
}
