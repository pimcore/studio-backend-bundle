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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Security\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Mcp\McpAccessToken;
use Pimcore\Bundle\StudioBackendBundle\Security\Exception\McpTokenUserInvalidException;
use Pimcore\Bundle\StudioBackendBundle\Security\Repository\McpAccessTokenRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenService;
use Pimcore\Model\User;

final class McpAccessTokenServiceTest extends Unit
{
    private const TOKEN_PREFIX = 'pmcp_';

    public function testIssueDeletesPriorTokenAndPersistsFreshHash(): void
    {
        $deletedRef = null;
        $saved = null;
        $service = $this->makeService(
            userValid: true,
            repoOverrides: [
                'deleteByReference' => function (string $r) use (&$deletedRef): void { $deletedRef = $r; },
                'save' => function (McpAccessToken $t) use (&$saved): void { $saved = $t; },
            ],
        );

        $token = $service->issue(42, 7200, 'chat-1');

        // exactly-one-live-token-per-reference: prior row(s) for the reference are cleared first
        $this->assertSame('chat-1', $deletedRef);
        $this->assertStringStartsWith(self::TOKEN_PREFIX, $token);
        $this->assertInstanceOf(McpAccessToken::class, $saved);
        $this->assertSame(hash('sha256', $token), $saved->getTokenHash());
        $this->assertSame(42, $saved->getUserId());
        $this->assertSame('chat-1', $saved->getReference());
    }

    public function testIssueThrowsWhenUserInvalid(): void
    {
        $service = $this->makeService(userValid: false);
        $this->expectException(McpTokenUserInvalidException::class);
        $service->issue(42, 7200, 'chat-1');
    }

    public function testValidateReturnsNullForExpiredToken(): void
    {
        $service = $this->makeService(
            userValid: true,
            repoOverrides: ['findByHash' => fn () => new McpAccessToken('h', 42, 'chat-1', 1, 1)],
        );
        $this->assertNull($service->validate('pmcp_whatever'));
    }

    public function testValidateReturnsUserAndReferenceForLiveToken(): void
    {
        $service = $this->makeService(
            userValid: true,
            repoOverrides: [
                'findByHash' => fn () => new McpAccessToken('h', 42, 'chat-7', time() + 3600, time()),
            ],
        );
        $result = $service->validate('pmcp_live');

        $this->assertNotNull($result);
        $this->assertSame('chat-7', $result->reference);
        $this->assertSame('agent-user', $result->user->getUsername());
    }

    public function testRefreshReturnsFalseWhenNoTokenForReference(): void
    {
        $service = $this->makeService(
            userValid: true,
            repoOverrides: ['findOneByReference' => fn () => null],
        );
        $this->assertFalse($service->refresh('chat-unknown', 7200));
    }

    /**
     * @param array<string, callable> $repoOverrides
     */
    private function makeService(bool $userValid, array $repoOverrides = []): McpAccessTokenService
    {
        $user = new User();
        $user->setId(42);
        $user->setUsername('agent-user');

        $repo = $this->makeEmpty(McpAccessTokenRepositoryInterface::class, $repoOverrides);
        $auth = $this->makeEmpty(AuthenticationResolverInterface::class, [
            'isValidUser' => $userValid,
        ]);

        return new McpAccessTokenService($repo, $auth, fn (int $id) => $userValid ? $user : null);
    }
}
