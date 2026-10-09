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
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\PendingSessionChecker;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\TwoFactor\Fixture\CustomTwoFactorToken;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class PendingSessionCheckerTest extends Unit
{
    public function testSessionWaitingForTheCodeIsPending(): void
    {
        $token = new TwoFactorToken($this->passwordToken(), null, 'pimcore_studio', ['google']);

        $this->assertTrue((new PendingSessionChecker())->isCodePending($this->request(serialize($token))));
    }

    /**
     * Scheb lets an installation swap the token class; a pending login must not pass as a full session then.
     */
    public function testSessionWaitingForTheCodeWithACustomTokenClassIsPending(): void
    {
        $token = new CustomTwoFactorToken($this->passwordToken(), null, 'pimcore_studio', ['google']);

        $this->assertTrue((new PendingSessionChecker())->isCodePending($this->request(serialize($token))));
    }

    public function testSessionWithAnUnknownTokenClassIsNotPending(): void
    {
        $this->assertFalse(
            (new PendingSessionChecker())->isCodePending($this->request('O:12:"Acme\\NoSuch":0:{}'))
        );
    }

    public function testSessionValueThatIsNoSerializedObjectIsNotPending(): void
    {
        $this->assertFalse((new PendingSessionChecker())->isCodePending($this->request(serialize('a token'))));
    }

    public function testCompletedLoginIsNotPending(): void
    {
        $this->assertFalse(
            (new PendingSessionChecker())->isCodePending($this->request(serialize($this->passwordToken())))
        );
    }

    public function testRequestWithoutSessionIsNotPending(): void
    {
        $this->assertFalse((new PendingSessionChecker())->isCodePending(Request::create('/')));
    }

    public function testSessionWithoutTokenIsNotPending(): void
    {
        $this->assertFalse((new PendingSessionChecker())->isCodePending($this->request(null)));
    }

    private function passwordToken(): UsernamePasswordToken
    {
        return new UsernamePasswordToken(new InMemoryUser('john', null), 'pimcore_studio');
    }

    private function request(?string $serializedToken): Request
    {
        $session = new Session(new MockArraySessionStorage());
        if ($serializedToken !== null) {
            $session->set('_security_pimcore_admin', $serializedToken);
        }

        $request = Request::create('/');
        $request->setSession($session);
        $request->cookies->set($session->getName(), 'session-id');

        return $request;
    }
}
