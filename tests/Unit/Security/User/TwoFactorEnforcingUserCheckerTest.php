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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\User;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Security\User\TwoFactorEnforcingUserChecker;
use Pimcore\Model\User as PimcoreUser;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Security\User\UserChecker as PimcoreUserChecker;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
final class TwoFactorEnforcingUserCheckerTest extends Unit
{
    public function testCheckPreAuthDelegatesToTheDecoratedCheckerWithoutEvaluatingTwoFactorState(): void
    {
        $decorated = $this->createMock(PimcoreUserChecker::class);
        $decorated->expects($this->once())->method('checkPreAuth');

        // Pre-auth happens before credentials are verified; even a 2FA-required account must
        // not be rejected (or otherwise singled out) at this stage.
        $user = $this->studioUser(required: true, enabled: true);

        (new TwoFactorEnforcingUserChecker($decorated))->checkPreAuth($user);
    }

    public function testCheckPostAuthDelegatesToTheDecoratedCheckerFirst(): void
    {
        $decorated = $this->createMock(PimcoreUserChecker::class);
        $decorated->expects($this->once())
            ->method('checkPostAuth')
            ->willThrowException(new CustomUserMessageAccountStatusException('invalid user'));

        $user = $this->studioUser(required: false, enabled: false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('invalid user');

        (new TwoFactorEnforcingUserChecker($decorated))->checkPostAuth($user);
    }

    public function testAllowsAuthenticationForAnAccountWithoutTwoFactorAuthentication(): void
    {
        $decorated = $this->createMock(PimcoreUserChecker::class);
        $decorated->expects($this->once())->method('checkPostAuth');

        $user = $this->studioUser(required: false, enabled: false);

        (new TwoFactorEnforcingUserChecker($decorated))->checkPostAuth($user);

        $this->assertTrue(true, 'checkPostAuth must not throw for a non-2FA account');
    }

    public function testDeniesAuthenticationWhenTwoFactorAuthenticationIsEnabled(): void
    {
        $decorated = $this->createMock(PimcoreUserChecker::class);
        $decorated->expects($this->once())->method('checkPostAuth');

        $user = $this->studioUser(required: false, enabled: true);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new TwoFactorEnforcingUserChecker($decorated))->checkPostAuth($user);
    }

    public function testDeniesAuthenticationWhenTwoFactorAuthenticationIsRequired(): void
    {
        $decorated = $this->createMock(PimcoreUserChecker::class);
        $decorated->expects($this->once())->method('checkPostAuth');

        $user = $this->studioUser(required: true, enabled: false);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new TwoFactorEnforcingUserChecker($decorated))->checkPostAuth($user);
    }

    public function testIgnoresNonPimcoreUsersAndOnlyDelegates(): void
    {
        $decorated = $this->createMock(PimcoreUserChecker::class);
        $decorated->expects($this->once())->method('checkPostAuth');

        $user = $this->createStub(UserInterface::class);

        (new TwoFactorEnforcingUserChecker($decorated))->checkPostAuth($user);

        $this->assertTrue(true, 'checkPostAuth must not throw for a non-Pimcore user object');
    }

    private function studioUser(bool $required, bool $enabled): SecurityUser
    {
        // Pimcore\Model\User is final, and both types are cheap value-ish objects, so build
        // them for real rather than doubling.
        $pimcoreUser = new PimcoreUser();
        $pimcoreUser->setTwoFactorAuthentication('required', $required);
        $pimcoreUser->setTwoFactorAuthentication('enabled', $enabled);

        return new SecurityUser($pimcoreUser);
    }
}
