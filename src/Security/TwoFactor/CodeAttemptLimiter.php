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

namespace Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\EventListener\CheckTwoFactorCodeListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use function ceil;
use function time;

/**
 * Limits two-factor code attempts per user; a correct code resets the count. The firewall's login
 * throttling does not do it: every successful password login resets that limiter, which would hand
 * out fresh guesses at the code. Keyed on the user, not the address, because only someone with the
 * password gets this far.
 *
 * @internal
 */
final readonly class CodeAttemptLimiter implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $limiterFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before scheb checks the code, so the right code is refused too once the limit is hit.
            CheckPassportEvent::class => ['checkPassport', CheckTwoFactorCodeListener::LISTENER_PRIORITY + 1],
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $limiter = $this->limiter($event->getPassport());
        if ($limiter === null) {
            return;
        }

        // Counted before the code is checked, so parallel attempts cannot all slip under the limit.
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyLoginAttemptsAuthenticationException(
                (int) ceil(($limit->getRetryAfter()->getTimestamp() - time()) / 60)
            );
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->limiter($event->getPassport())?->reset();
    }

    private function limiter(?Passport $passport): ?LimiterInterface
    {
        if ($passport === null || !$passport->hasBadge(TwoFactorCodeCredentials::class)) {
            return null;
        }

        $userBadge = $passport->getBadge(UserBadge::class);
        if (!$userBadge instanceof UserBadge) {
            return null;
        }

        return $this->limiterFactory->create($userBadge->getUserIdentifier());
    }
}
