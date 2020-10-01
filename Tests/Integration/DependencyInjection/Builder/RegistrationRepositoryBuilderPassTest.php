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

namespace OAT\Bundle\Lti1p3Bundle\Tests\Integration\DependencyInjection\Builder;

use InvalidArgumentException;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Builder\RegistrationRepositoryBuilder;
use OAT\Bundle\Lti1p3Bundle\Repository\RegistrationRepository;
use OAT\Bundle\Lti1p3Bundle\Tests\Resources\Kernel\Lti1p3TestKernel;
use OAT\Library\Lti1p3Core\Platform\PlatformFactory;
use OAT\Library\Lti1p3Core\Platform\PlatformInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationFactory;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Tool\ToolFactory;
use OAT\Library\Lti1p3Core\Tool\ToolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @see Lti1p3TestKernel */
class RegistrationRepositoryBuilderPassTest extends KernelTestCase
{
    /** @var RegistrationRepositoryBuilder */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new RegistrationRepositoryBuilder(
            new KeyChainFactory(),
            new PlatformFactory(),
            new ToolFactory(),
            new RegistrationFactory()
        );
    }

    public function testBuiltContainerRepositoryCanFindRegistration(): void
    {
        static::bootKernel();

        $repository = static::$container->get(RegistrationRepositoryInterface::class);

        $this->assertInstanceOf(RegistrationRepository::class, $repository);

        $registration = $repository->find('testRegistration');

        $this->assertInstanceOf(RegistrationInterface::class, $registration);
        $this->assertEquals('testRegistration', $registration->getIdentifier());
        $this->assertEquals('client_id', $registration->getClientId());
        $this->assertInstanceOf(PlatformInterface::class, $registration->getPlatform());
        $this->assertInstanceOf(ToolInterface::class, $registration->getTool());
    }

    public function testBuildFailsOnInvalidPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform invalid is not defined, possible values: testPlatform');

        $this->subject->build($this->generateTestingConfiguration('invalid'));
    }

    public function testBuildFailsOnInvalidPlatformKeyChain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Platform key chain invalid is not defined, possible values: kid1, kid2');

        $this->subject->build($this->generateTestingConfiguration('testPlatform', 'testTool', 'invalid'));
    }

    public function testBuildFailsOnInvalidTool(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tool invalid is not defined, possible values: testTool');

        $this->subject->build($this->generateTestingConfiguration('testPlatform', 'invalid'));
    }

    public function testBuildFailsOnInvalidToolKeyChain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tool key chain invalid is not defined, possible values: kid1, kid2');

        $this->subject->build($this->generateTestingConfiguration('testPlatform', 'testTool', 'kid1', 'invalid'));
    }

    private function generateTestingConfiguration(
        $platform = 'testPlatform',
        $tool = 'testTool',
        $platformKeyChain = 'kid1',
        $toolKeyChain = 'kid2'
    ): array {
        return [
            'key_chains' => [
                'kid1' => [
                'key_set_name' => 'platformSet',
                'public_key' => 'file://%kernel.project_dir%/Tests/Resources/Keys/public.key',
                'private_key' => 'file://%kernel.project_dir%/Tests/Resources/Keys/private.key',
                'private_key_passphrase' => null,
                ],
                'kid2' => [
                    'key_set_name' => 'toolSet',
                    'public_key' => 'file://%kernel.project_dir%/Tests/Resources/Keys/public.key',
                    'private_key' => 'file://%kernel.project_dir%/Tests/Resources/Keys/private.key',
                    'private_key_passphrase' => null,
                ],
            ],
            'platforms' => [
                'testPlatform' => [
                    'name' => 'Test platform',
                    'audience' => 'http://platform.com',
                    'oidc_authentication_url' => 'http://platform.com/oidc-auth',
                    'oauth2_access_token_url' => 'http://platform.com/access-token',
                ],
            ],
            'tools' => [
                'testTool' => [
                    'name' => 'Test tool',
                    'audience' => 'http://tool.com',
                    'oidc_initiation_url' => 'http://tool.com/oidc-init',
                    'launch_url' => 'http://tool.com/launch',
                    'deep_linking_url' => 'http://tool.com/deep-linking',
                ]
            ],
            'registrations' => [
                'testRegistration' => [
                    'client_id' => 'client_id',
                    'platform' => $platform,
                    'tool' => $tool,
                    'deployment_ids' => [
                        'deploymentId1',
                        'deploymentId2',
                    ],
                    'platform_key_chain' => $platformKeyChain,
                    'tool_key_chain' => $toolKeyChain,
                    'platform_jwks_url' => null,
                    'tool_jwks_url' => null,
                ]
            ]
        ];
    }
}
