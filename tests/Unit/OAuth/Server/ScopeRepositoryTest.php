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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;

final class ScopeRepositoryTest extends Unit
{
    public function testResolvesSupportedScopes(): void
    {
        $repo = $this->repository('mcp:read', 'mcp:write');
        $this->assertInstanceOf(ScopeEntity::class, $repo->getScopeEntityByIdentifier('mcp:read'));
        $this->assertInstanceOf(ScopeEntity::class, $repo->getScopeEntityByIdentifier('mcp:write'));
    }

    public function testRejectsUnknownScope(): void
    {
        $repo = $this->repository('mcp:read', 'mcp:write');
        $this->assertNull($repo->getScopeEntityByIdentifier('mcp:admin'));
    }

    public function testResolvesScopeContributedByAnotherBundle(): void
    {
        // The catalogue is not hardcoded: a scope a provider contributes resolves.
        $repo = $this->repository('datahub:read');

        $scope = $repo->getScopeEntityByIdentifier('datahub:read');
        $this->assertInstanceOf(ScopeEntity::class, $scope);
        $this->assertSame('datahub:read', $scope->getIdentifier());
    }

    public function testRejectsScopeMissingFromTheRegistry(): void
    {
        // Same identifier, different catalogue: with no provider contributing
        // `mcp:write` it must be refused, proving the registry is what decides.
        $this->assertNull($this->repository('mcp:read')->getScopeEntityByIdentifier('mcp:write'));
    }

    public function testFinalizeScopesPassesValidatedScopesThrough(): void
    {
        $scopes = [new ScopeEntity('mcp:read')];
        $finalized = $this->repository('mcp:read')->finalizeScopes(
            $scopes,
            'authorization_code',
            new ClientEntity('studio-mcp', 'Studio MCP', [], true),
        );

        $this->assertSame($scopes, $finalized);
    }

    /**
     * A repository backed by the real registry, fed by a single provider that
     * contributes exactly the given scopes.
     */
    private function repository(string ...$scopes): ScopeRepository
    {
        $provider = new class($scopes) implements ScopeProviderInterface {
            /**
             * @param list<string> $scopes
             */
            public function __construct(private readonly array $scopes)
            {
            }

            public function scopes(): array
            {
                return $this->scopes;
            }
        };

        return new ScopeRepository(new ScopeRegistry([$provider]));
    }
}
