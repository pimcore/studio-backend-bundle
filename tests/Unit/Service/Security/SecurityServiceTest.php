<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\Security;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\User as PimcoreUser;

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

        $this->expectException(AccessDeniedException::class);
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
}
