<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\Security;

use PHPUnit\Framework\TestCase;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\User as PimcoreUser;

final class SecurityServiceTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService::getCurrentUser
     * @throws Exception
     */
    public function testGetCurrentUserWithOutValidUser(): void
    {
        $securityService = $this->mockSecurityService(false, false);

        $this->expectException(UserNotFoundException::class);
        $securityService->getCurrentUser();
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService::getCurrentUser
     * @throws Exception
     */
    public function testGetCurrentUserWithValidUser(): void
    {
        $securityService = $this->mockSecurityService(true, false);

        $user = $securityService->getCurrentUser();

        $this->assertInstanceOf(PimcoreUser::class, $user);
        $this->assertSame('test', $user->getUsername());
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService::hasElementPermission
     * @throws Exception
     */
    public function testHasElementPermission(): void
    {
        $securityService = $this->mockSecurityService(
            true,
            false
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('You dont have speak up permission');
        $securityService->hasElementPermission(
            new Asset(),
            new PimcoreUser(),
            'speak up'
        );
    }

    /**
     * @throws Exception
     */
    private function mockSecurityService(
        bool $withUser = true,
        bool $hasPermission = true,
    ): SecurityServiceInterface {
        return new SecurityService(
            $this->mockElementPermissionService($hasPermission),
            $this->mockAuthenticationResolver($withUser)
        );
    }

    private function mockElementPermissionService(bool $hasPermission): ElementPermissionServiceInterface
    {
        $mock = $this->createMock(ElementPermissionServiceInterface::class);
        $mock->method('isAllowed')->willReturn($hasPermission);
        
        return $mock;
    }

    private function mockAuthenticationResolver(bool $withUser): AuthenticationResolverInterface
    {
        $user = new PimcoreUser();
        $user->setUsername('test');

        $mock = $this->createMock(AuthenticationResolverInterface::class);
        $mock->method('authenticateSession')->willReturn($withUser ? $user : null);
        
        return $mock;
    }
}
