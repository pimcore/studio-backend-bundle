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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\Authenticator\Mcp;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\SessionBridgeAuthenticator;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\PendingSessionCheckerInterface;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class SessionBridgeAuthenticatorTest extends Unit
{
    /**
     * Core's session helper returns the user of a session whose two-factor code is still
     * pending; the bridge must not turn that into an MCP login.
     */
    public function testSessionWaitingForTheCodeIsRefused(): void
    {
        $resolver = $this->makeEmpty(AuthenticationResolverInterface::class, [
            'authenticateSession' => new User(),
            'isValidUser' => true,
        ]);

        $authenticator = new SessionBridgeAuthenticator($resolver, $this->checker(pending: true));

        $this->expectException(AuthenticationException::class);
        $authenticator->authenticate(Request::create('/pimcore-mcp/agent/meta'));
    }

    public function testCompletedSessionIsBridged(): void
    {
        $user = new User();
        $user->setName('john');
        $resolver = $this->makeEmpty(AuthenticationResolverInterface::class, [
            'authenticateSession' => $user,
            'isValidUser' => true,
        ]);

        $passport = (new SessionBridgeAuthenticator($resolver, $this->checker(pending: false)))
            ->authenticate(Request::create('/pimcore-mcp/agent/meta'));

        $this->assertSame('john', $passport->getUser()->getUserIdentifier());
    }

    private function checker(bool $pending): PendingSessionCheckerInterface
    {
        return $this->makeEmpty(PendingSessionCheckerInterface::class, ['isCodePending' => $pending]);
    }
}
