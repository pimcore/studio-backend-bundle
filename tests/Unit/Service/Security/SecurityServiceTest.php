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

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\User as PimcoreUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SecurityServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetCurrentUserWithOutValidUser(): void
    {
        $securityService = $this->mockSecurityService(false, false);

        $this->expectException(UserNotFoundException::class);
        $securityService->getCurrentUser();
    }

    /**
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
            $this->mockAuthenticationResolver($withUser),
            $this->mockTokenStorage()
        );
    }

    private function mockElementPermissionService(bool $hasPermission): ElementPermissionServiceInterface
    {
        return $this->makeEmpty(ElementPermissionServiceInterface::class, [
            'isAllowed' => $hasPermission,
        ]);
    }

    private function mockAuthenticationResolver(bool $withUser): AuthenticationResolverInterface
    {
        $user = new PimcoreUser();
        $user->setUsername('test');

        return $this->makeEmpty(AuthenticationResolverInterface::class, [
            'authenticateSession' => $withUser ? $user : null,
        ]);
    }

    private function mockTokenStorage(): TokenStorageInterface
    {
        return $this->makeEmpty(TokenStorageInterface::class);
    }
}
