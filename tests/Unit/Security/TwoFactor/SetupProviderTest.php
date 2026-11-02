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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\TwoFactor;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\SetupProvider;
use Pimcore\Bundle\StudioBackendBundle\User\Service\TwoFactorServiceInterface;
use Pimcore\Model\User as PimcoreUser;
use Pimcore\Security\User\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class SetupProviderTest extends Unit
{
    /**
     * @dataProvider userProvider
     */
    public function testStartsOnlyForUsersWhoMustSetUpTwoFactor(bool $required, bool $enabled, bool $expected): void
    {
        $context = $this->makeEmpty(AuthenticationContextInterface::class, [
            'getUser' => $this->user($required, $enabled),
        ]);

        $this->assertSame($expected, $this->provider()->beginAuthentication($context));
    }

    /**
     * @return array<string, array{0: bool, 1: bool, 2: bool}>
     */
    public static function userProvider(): array
    {
        return [
            'neither' => [false, false, false],
            'required, not set up' => [true, false, true],
            'required and set up' => [true, true, false],
            'set up, not required' => [false, true, false],
        ];
    }

    public function testOtherUserTypesNeverStart(): void
    {
        $context = $this->makeEmpty(AuthenticationContextInterface::class, [
            'getUser' => new InMemoryUser('john', null),
        ]);

        $this->assertFalse($this->provider()->beginAuthentication($context));
    }

    /**
     * Without this, scheb refuses every code as "not prepared": Studio never shows scheb's form.
     */
    public function testNeedsNoPreparation(): void
    {
        $this->assertFalse($this->provider()->needsPreparation());
    }

    public function testCodeMatchingThePendingSecretConfirmsTheSetup(): void
    {
        $user = $this->user(true, false);
        $service = $this->makeEmpty(TwoFactorServiceInterface::class, [
            'confirmSetup' => Expected::once(function ($pimcoreUser, string $code) use ($user): void {
                $this->assertSame($user->getUser(), $pimcoreUser);
                $this->assertSame('123456', $code);
            }),
        ]);

        $this->assertTrue((new SetupProvider($service))->validateAuthenticationCode($user, '123456'));
    }

    public function testWrongCodeIsNotValid(): void
    {
        $service = $this->makeEmpty(TwoFactorServiceInterface::class, [
            'confirmSetup' => static fn () => throw new UnprocessableContentException('Invalid code.'),
        ]);

        $this->assertFalse((new SetupProvider($service))->validateAuthenticationCode($this->user(true, false), '1'));
    }

    public function testCodeWithoutAPendingSecretIsNotValid(): void
    {
        $service = $this->makeEmpty(TwoFactorServiceInterface::class, [
            'confirmSetup' => static fn () => throw new ConflictException('No two-factor setup is pending.'),
        ]);

        $this->assertFalse((new SetupProvider($service))->validateAuthenticationCode($this->user(true, false), '1'));
    }

    public function testRefusedSetupIsNotValid(): void
    {
        $service = $this->makeEmpty(TwoFactorServiceInterface::class, [
            'confirmSetup' => static fn () => throw new ForbiddenException('Not completed.'),
        ]);

        $this->assertFalse((new SetupProvider($service))->validateAuthenticationCode($this->user(true, true), '1'));
    }

    public function testCodeForAnotherUserTypeIsNotValid(): void
    {
        $service = $this->makeEmpty(TwoFactorServiceInterface::class, ['confirmSetup' => Expected::never()]);

        $provider = new SetupProvider($service);

        $this->assertFalse($provider->validateAuthenticationCode(new InMemoryUser('john', null), '1'));
    }

    private function provider(): SetupProvider
    {
        return new SetupProvider($this->makeEmpty(TwoFactorServiceInterface::class));
    }

    private function user(bool $required, bool $enabled): User
    {
        $user = new PimcoreUser();
        $user->setTwoFactorAuthentication('required', $required);
        $user->setTwoFactorAuthentication('enabled', $enabled);

        return new User($user);
    }
}
