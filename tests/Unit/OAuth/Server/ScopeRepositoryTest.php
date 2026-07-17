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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;

final class ScopeRepositoryTest extends Unit
{
    public function testResolvesSupportedScopes(): void
    {
        $repo = new ScopeRepository();
        $this->assertInstanceOf(ScopeEntity::class, $repo->getScopeEntityByIdentifier('mcp:read'));
        $this->assertInstanceOf(ScopeEntity::class, $repo->getScopeEntityByIdentifier('mcp:write'));
    }

    public function testRejectsUnknownScope(): void
    {
        $this->assertNull((new ScopeRepository())->getScopeEntityByIdentifier('mcp:admin'));
    }

    public function testFinalizeScopesPassesValidatedScopesThrough(): void
    {
        $scopes = [new ScopeEntity('mcp:read')];
        $finalized = (new ScopeRepository())->finalizeScopes(
            $scopes,
            'client_credentials',
            new ClientEntity('studio-mcp', 'Studio MCP', [], true, 21),
        );

        $this->assertSame($scopes, $finalized);
    }
}
