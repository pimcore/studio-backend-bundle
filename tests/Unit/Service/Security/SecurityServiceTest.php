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
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\Credentials;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Service\SecurityService;
use Pimcore\Bundle\StudioBackendBundle\Service\SecurityServiceInterface;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Security\User\User;
use Pimcore\Security\User\UserProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class SecurityServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testSecurityService(): void
    {
        $securityService = $this->mockSecurityService();
        $user = $securityService->authenticateUser(new Credentials('test', 'test'));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test', $user->getPassword());
    }

    /**
     * @throws Exception
     */
    public function testInvalidPassword(): void
    {
        $securityService = $this->mockSecurityService(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Bad credentials');
        $securityService->authenticateUser(new Credentials('test', 'test'));
    }

    /**
     * @throws Exception
     */
    public function testUserNotFound(): void
    {
        $securityService = $this->mockSecurityService(false, false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Bad credentials');
        $securityService->authenticateUser(new Credentials('test', 'test'));
    }

    /**
     * @throws Exception
     */
    public function testTokenAllowedTrue(): void
    {
        $securityService = $this->mockSecurityService(false, false);

        $this->assertTrue($securityService->checkAuthToken('test'));
    }

    /**
     * @throws Exception
     */
    public function testTokenAllowedFalse(): void
    {
        $securityService = $this->mockSecurityService(false, false, false);

        $this->assertFalse($securityService->checkAuthToken('test'));
    }

    /**
     * @throws Exception
     */
    private function mockSecurityService($validPassword = true, bool $withUser = true, bool $withTmpStore = true): SecurityServiceInterface
    {
        return new SecurityService(
            $withUser ? $this->mockUserProviderWithUser() : $this->mockUserProviderWithOutUser(),
            $this->mockPasswordHasher($validPassword),
            $this->mockTmpStoreResolver($withTmpStore)
        );
    }

    /**
     * @throws Exception
     */
    private function mockUserProviderWithUser(): UserProvider
    {
        return $this->makeEmpty(UserProvider::class, [
            'loadUserByIdentifier' => function () {
                return $this->makeEmpty(User::class, [
                    'getPassword' => 'test',
                ]);
            },
        ]);
    }

    /**
     * @throws Exception
     */
    private function mockUserProviderWithOutUser(): UserProvider
    {
        return $this->makeEmpty(UserProvider::class, [
            'loadUserByIdentifier' => fn () => throw new UserNotFoundException('User not found'),
        ]);
    }

    /**
     * @throws Exception
     */
    private function mockPasswordHasher($validPassword = true): UserPasswordHasherInterface
    {
        return $this->makeEmpty(UserPasswordHasherInterface::class, [
            'isPasswordValid' => $validPassword,
        ]);
    }

    /**
     * @throws Exception
     */
    private function mockTmpStoreResolver($withTmpStore = true): TmpStoreResolverInterface
    {
        return $this->makeEmpty(TmpStoreResolverInterface::class, [
            'get' => $withTmpStore ? $this->mockTmpStore() : null,
        ]);
    }

    private function mockTmpStore(): TmpStore
    {
        $tmpStore = new TmpStore();
        $tmpStore->setId('test');

        return $tmpStore;
    }
}
