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

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\CodeAttemptLimiter;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class CodeAttemptLimiterTest extends Unit
{
    private const int LIMIT = 5;

    private CodeAttemptLimiter $limiter;

    protected function _before(): void
    {
        $this->limiter = new CodeAttemptLimiter(new RateLimiterFactory(
            ['id' => 'two_factor_code', 'policy' => 'fixed_window', 'limit' => self::LIMIT, 'interval' => '5 minutes'],
            new InMemoryStorage()
        ));
    }

    public function testAttemptsBelowTheLimitPass(): void
    {
        $this->failCodes('john', self::LIMIT - 1);

        $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
        $this->addToAssertionCount(1);
    }

    /**
     * Refused before the code is even checked, so the right code is refused too.
     */
    public function testAttemptAfterTheLimitIsRefused(): void
    {
        $this->failCodes('john', self::LIMIT);

        $this->expectException(TooManyLoginAttemptsAuthenticationException::class);
        $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
    }

    /**
     * Logging in with the password again must not buy more guesses at the code.
     */
    public function testPasswordLoginDoesNotResetTheCount(): void
    {
        $this->failCodes('john', self::LIMIT);
        $this->limiter->onLoginSuccess($this->successEvent($this->passwordPassport('john')));

        $this->expectException(TooManyLoginAttemptsAuthenticationException::class);
        $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
    }

    public function testCorrectCodeResetsTheCount(): void
    {
        $this->failCodes('john', self::LIMIT - 1);
        $this->limiter->onLoginSuccess($this->successEvent($this->codePassport('john')));
        $this->failCodes('john', self::LIMIT - 1);

        $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
        $this->addToAssertionCount(1);
    }

    public function testCountIsPerUser(): void
    {
        $this->failCodes('john', self::LIMIT);

        $this->limiter->checkPassport($this->checkEvent($this->codePassport('jane')));
        $this->addToAssertionCount(1);
    }

    public function testPasswordAttemptsAreNotCounted(): void
    {
        for ($i = 0; $i < self::LIMIT; ++$i) {
            $this->limiter->checkPassport($this->checkEvent($this->passwordPassport('john')));
        }

        $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
        $this->addToAssertionCount(1);
    }

    /**
     * Each attempt is counted before its code is checked, so attempts sent in parallel from
     * several pending logins cannot all pass the limit before any of them failed.
     */
    public function testParallelAttemptsAreCountedBeforeTheCodeIsChecked(): void
    {
        for ($i = 0; $i < self::LIMIT; ++$i) {
            $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
        }

        $this->expectException(TooManyLoginAttemptsAuthenticationException::class);
        $this->limiter->checkPassport($this->checkEvent($this->codePassport('john')));
    }

    private function failCodes(string $user, int $times): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $this->limiter->checkPassport($this->checkEvent($this->codePassport($user)));
        }
    }

    private function codePassport(string $user): Passport
    {
        $token = $this->makeEmpty(TwoFactorTokenInterface::class);

        return new Passport(new UserBadge($user), new TwoFactorCodeCredentials($token, '123456'));
    }

    private function passwordPassport(string $user): Passport
    {
        return new Passport(new UserBadge($user), new PasswordCredentials('secret'));
    }

    private function checkEvent(Passport $passport): CheckPassportEvent
    {
        return new CheckPassportEvent($this->makeEmpty(AuthenticatorInterface::class), $passport);
    }

    private function successEvent(Passport $passport): LoginSuccessEvent
    {
        return new LoginSuccessEvent(
            $this->makeEmpty(AuthenticatorInterface::class),
            $passport,
            $this->makeEmpty(TokenInterface::class),
            Request::create('/'),
            null,
            'pimcore_studio'
        );
    }
}
