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
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpAuthType;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpRequestAttributes;
use Pimcore\Bundle\StudioBackendBundle\Security\Authenticator\Mcp\SessionBridgeAuthenticator;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class SessionBridgeAuthenticatorTest extends Unit
{
    public function testAuthenticateExposesAuthTypeWithoutScopes(): void
    {
        $user = new User();
        $user->setUsername('agent-user');
        $auth = $this->makeAuthenticator($user, true);

        $request = new Request();
        $passport = $auth->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame(McpAuthType::Session, $request->attributes->get(McpRequestAttributes::AUTH_TYPE));
        $this->assertFalse($request->attributes->has(McpRequestAttributes::OAUTH_SCOPES));
    }

    public function testFailedAuthenticationSetsNoAuthType(): void
    {
        $auth = $this->makeAuthenticator(null, false);
        $request = new Request();

        try {
            $auth->authenticate($request);
            $this->fail('Expected an AuthenticationException.');
        } catch (AuthenticationException) {
            // Expected: only the attributes matter.
        }

        $this->assertFalse($request->attributes->has(McpRequestAttributes::AUTH_TYPE));
    }

    private function makeAuthenticator(?User $user, bool $valid): SessionBridgeAuthenticator
    {
        return new SessionBridgeAuthenticator(
            $this->makeEmpty(AuthenticationResolverInterface::class, [
                'authenticateSession' => $user,
                'isValidUser' => $valid,
            ]),
        );
    }
}
