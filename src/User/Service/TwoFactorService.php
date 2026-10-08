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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\User\Event\TwoFactorSetupEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorSetup;
use Pimcore\Bundle\StudioBackendBundle\User\TwoFactor\PendingSecret;
use Pimcore\Model\UserInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use function is_array;

/**
 * @internal
 */
final readonly class TwoFactorService implements TwoFactorServiceInterface
{
    // Only in the session until a first code confirms it, so a renewal keeps the old secret valid.
    private const string SESSION_KEY = 'pimcore_studio_two_factor_pending_secret';

    private const string TYPE_GOOGLE = 'google';

    public function __construct(
        private GoogleAuthenticatorInterface $googleAuthenticator,
        private UserRepositoryInterface $userRepository,
        private RequestStack $requestStack,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createSetup(UserInterface $user): TwoFactorSetup
    {
        $secret = $this->googleAuthenticator->generateSecret();
        $this->requestStack->getSession()->set(self::SESSION_KEY, ['userId' => $user->getId(), 'secret' => $secret]);

        $setup = new TwoFactorSetup(
            $secret,
            $this->googleAuthenticator->getQRContent(new PendingSecret($user->getName(), $secret))
        );
        $this->eventDispatcher->dispatch(new TwoFactorSetupEvent($setup), TwoFactorSetupEvent::EVENT_NAME);

        return $setup;
    }

    public function confirmSetup(UserInterface $user, string $code): void
    {
        $session = $this->requestStack->getSession();
        $pending = $session->get(self::SESSION_KEY);
        if (!is_array($pending) || ($pending['userId'] ?? null) !== $user->getId()) {
            throw new ConflictException('No two-factor setup is pending.');
        }

        $secret = (string) $pending['secret'];
        if (!$this->googleAuthenticator->checkCode(new PendingSecret($user->getName(), $secret), $code)) {
            throw new UnprocessableContentException('Invalid code.');
        }

        $user->setTwoFactorAuthentication('enabled', true);
        $user->setTwoFactorAuthentication('type', self::TYPE_GOOGLE);
        $user->setTwoFactorAuthentication('secret', $secret);
        $this->userRepository->updateUser($user);
        $session->remove(self::SESSION_KEY);
    }

    public function disable(UserInterface $user): void
    {
        if ($user->getTwoFactorAuthentication('required')) {
            throw new ForbiddenException('Two-factor authentication is required for this user.');
        }

        $user->setTwoFactorAuthentication('enabled', false);
        $user->setTwoFactorAuthentication('type', '');
        $user->setTwoFactorAuthentication('secret', '');
        $this->userRepository->updateUser($user);
    }
}
