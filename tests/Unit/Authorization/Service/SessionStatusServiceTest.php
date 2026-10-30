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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Authorization\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Service\SessionStatusService;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupProvider;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
final class SessionStatusServiceTest extends Unit
{
    public function testStudioUserIsAuthenticated(): void
    {
        $status = $this->createService(true, new TokenStorage())->getSessionStatus();

        $this->assertTrue($status->isAuthenticated());
        $this->assertNull($status->getTwoFactorRequired());
        $this->assertNull($status->getTwoFactorStep());
        $this->assertNull($status->getDigits());
    }

    public function testWithoutSessionIsNotAuthenticated(): void
    {
        $status = $this->createService(false, new TokenStorage())->getSessionStatus();

        $this->assertFalse($status->isAuthenticated());
        $this->assertNull($status->getTwoFactorRequired());
    }

    public function testPendingCodeIsNotAuthenticatedAndCarriesTheVerifyStep(): void
    {
        $status = $this->createService(false, $this->storageWith('google'))->getSessionStatus();

        $this->assertFalse($status->isAuthenticated());
        $this->assertTrue($status->getTwoFactorRequired());
        $this->assertSame('verify', $status->getTwoFactorStep());
        $this->assertSame(6, $status->getDigits());
    }

    public function testPendingSetupCarriesTheSetupStep(): void
    {
        $status = $this->createService(false, $this->storageWith(SetupProvider::ALIAS), 8)->getSessionStatus();

        $this->assertFalse($status->isAuthenticated());
        $this->assertSame('setup', $status->getTwoFactorStep());
        $this->assertSame(8, $status->getDigits());
    }

    private function storageWith(string $provider): TokenStorage
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class, [
            'getCurrentTwoFactorProvider' => $provider,
        ]);
        $this->assertInstanceOf(TokenInterface::class, $token);

        $storage = new TokenStorage();
        $storage->setToken($token);

        return $storage;
    }

    private function createService(bool $granted, TokenStorage $tokenStorage, int $digits = 6): SessionStatusService
    {
        $checker = $this->makeEmpty(AuthorizationCheckerInterface::class, [
            'isGranted' => static fn (string $attribute): bool => $attribute === 'ROLE_PIMCORE_USER' && $granted,
        ]);

        return new SessionStatusService($tokenStorage, $checker, $digits);
    }
}
