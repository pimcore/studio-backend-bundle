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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use LogicException;
use OTPHP\TOTP;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\User\Event\TwoFactorSetupEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\TwoFactorService;
use Pimcore\Model\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleTotpFactory;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use function sprintf;

final class TwoFactorServiceTest extends Unit
{
    private RequestStack $requestStack;

    private EventDispatcher $eventDispatcher;

    // One frozen clock for the codes the tests make and the codes the service checks.
    private MockClock $clock;

    protected function _before(): void
    {
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);
        $this->eventDispatcher = new EventDispatcher();
        $this->clock = new MockClock('2026-10-08 12:00:10');
    }

    /**
     * Creating a secret changes nothing on the user: it waits in the session for a first code.
     */
    public function testSetupReturnsSecretAndUriAndSavesNothing(): void
    {
        $user = $this->user();

        $setup = $this->service($this->repository($user, saves: 0))->createSetup($user);

        $this->assertNotSame('', $setup->getSecret());
        $this->assertStringStartsWith('otpauth://totp/', $setup->getOtpauthUri());
        $this->assertStringContainsString('issuer=Acme%20PIM', $setup->getOtpauthUri());
        $this->assertStringContainsString('john%40pim.acme.test', $setup->getOtpauthUri());
        $this->assertStringContainsString('secret=' . $setup->getSecret(), $setup->getOtpauthUri());
        $this->assertFalse($user->getTwoFactorAuthentication('enabled'));
        $this->assertSame('', $user->getTwoFactorAuthentication('secret'));
    }

    public function testSetupResponseCanBeExtended(): void
    {
        $user = $this->user();
        $this->eventDispatcher->addListener(
            TwoFactorSetupEvent::EVENT_NAME,
            static fn (TwoFactorSetupEvent $event) => $event->addAdditionalAttribute('hint', 'x')
        );

        $setup = $this->service($this->repository($user, saves: 0))->createSetup($user);

        $this->assertSame('x', $setup->getAdditionalAttribute('hint'));
    }

    public function testRightCodeEnablesWithThePendingSecret(): void
    {
        $user = $this->user();
        $service = $this->service($this->repository($user, saves: 1));
        $secret = $service->createSetup($user)->getSecret();

        $service->confirmSetup($user, $this->code($secret));

        $this->assertTrue($user->getTwoFactorAuthentication('enabled'));
        $this->assertSame($secret, $user->getTwoFactorAuthentication('secret'));
        $this->assertSame('google', $user->getTwoFactorAuthentication('type'));
    }

    /**
     * A pending secret is used once; confirming again needs a new setup.
     */
    public function testPendingSecretIsGoneAfterConfirm(): void
    {
        $user = $this->user();
        $service = $this->service($this->repository($user, saves: 1));
        $secret = $service->createSetup($user)->getSecret();
        $service->confirmSetup($user, $this->code($secret));

        $this->expectException(ConflictException::class);
        $service->confirmSetup($user, $this->code($secret));
    }

    public function testWrongCodeIsRefusedAndSavesNothing(): void
    {
        $user = $this->user();
        $service = $this->service($this->repository($user, saves: 0));
        $secret = $service->createSetup($user)->getSecret();

        try {
            $service->confirmSetup($user, $this->wrongCode($secret));
            $this->fail('A wrong code was accepted.');
        } catch (UnprocessableContentException) {
        }

        $this->assertFalse($user->getTwoFactorAuthentication('enabled'));
        $this->assertSame('', $user->getTwoFactorAuthentication('secret'));
    }

    public function testWrongCodeKeepsTheSetupForAnotherTry(): void
    {
        $user = $this->user();
        $service = $this->service($this->repository($user, saves: 1));
        $secret = $service->createSetup($user)->getSecret();

        try {
            $service->confirmSetup($user, $this->wrongCode($secret));
        } catch (UnprocessableContentException) {
        }
        $service->confirmSetup($user, $this->code($secret));

        $this->assertTrue($user->getTwoFactorAuthentication('enabled'));
    }

    public function testConfirmWithoutSetupIsRefused(): void
    {
        $user = $this->user();

        $this->expectException(ConflictException::class);
        $this->service($this->repository($user, saves: 0))->confirmSetup($user, '123456');
    }

    /**
     * The pending secret belongs to the user who created it, even if the session is reused.
     */
    public function testAnotherUsersPendingSecretIsRefused(): void
    {
        $john = $this->user(id: 7);
        $jane = $this->user(id: 8);
        $service = $this->service($this->repository($jane, saves: 0));
        $secret = $service->createSetup($john)->getSecret();

        $this->expectException(ConflictException::class);
        $service->confirmSetup($jane, $this->code($secret));
    }

    /**
     * Renewing keeps the old secret valid until the new one is confirmed.
     */
    public function testRenewKeepsTheOldSecretUntilConfirmed(): void
    {
        $user = $this->user(enabled: true, secret: 'OLDSECRETOLDSECRET');
        $service = $this->service($this->repository($user, saves: 1));

        $newSecret = $service->createSetup($user)->getSecret();
        $this->assertSame('OLDSECRETOLDSECRET', $user->getTwoFactorAuthentication('secret'));
        $this->assertTrue($user->getTwoFactorAuthentication('enabled'));

        $service->confirmSetup($user, $this->code($newSecret));
        $this->assertSame($newSecret, $user->getTwoFactorAuthentication('secret'));
    }

    public function testDisableWhileRequiredIsRefusedAndChangesNothing(): void
    {
        $user = $this->user(enabled: true, secret: 'OLDSECRETOLDSECRET', required: true);

        try {
            $this->service($this->repository($user, saves: 0))->disable($user);
            $this->fail('Disabling a required two-factor authentication was accepted.');
        } catch (ForbiddenException) {
        }

        $this->assertTrue($user->getTwoFactorAuthentication('enabled'));
        $this->assertSame('OLDSECRETOLDSECRET', $user->getTwoFactorAuthentication('secret'));
    }

    public function testDisableClearsTheSecret(): void
    {
        $user = $this->user(enabled: true, secret: 'OLDSECRETOLDSECRET');

        $this->service($this->repository($user, saves: 1))->disable($user);

        $this->assertFalse($user->getTwoFactorAuthentication('enabled'));
        $this->assertSame('', $user->getTwoFactorAuthentication('secret'));
        $this->assertSame('', $user->getTwoFactorAuthentication('type'));
    }

    private function service(UserRepositoryInterface $repository): TwoFactorService
    {
        $authenticator = new GoogleAuthenticator(
            new GoogleTotpFactory('pim.acme.test', 'Acme PIM', 6, $this->clock),
            new EventDispatcher(),
            0
        );

        return new TwoFactorService($authenticator, $repository, $this->requestStack, $this->eventDispatcher);
    }

    private function repository(User $user, int $saves): UserRepositoryInterface
    {
        return $this->makeEmpty(UserRepositoryInterface::class, [
            'updateUser' => Expected::exactly($saves, static function (User $saved) use ($user): void {
                if ($saved !== $user) {
                    throw new LogicException('Saved another user.');
                }
            }),
        ]);
    }

    private function user(int $id = 7, bool $enabled = false, string $secret = '', bool $required = false): User
    {
        $user = new User();
        $user->setId($id);
        $user->setName('john');
        $user->setTwoFactorAuthentication([
            'required' => $required,
            'enabled' => $enabled,
            'secret' => $secret,
            'type' => $enabled ? 'google' : '',
        ]);

        return $user;
    }

    private function code(string $secret): string
    {
        return TOTP::createFromSecret($secret, $this->clock)->now();
    }

    private function wrongCode(string $secret): string
    {
        return sprintf('%06d', ((int) $this->code($secret) + 1) % 1000000);
    }
}
