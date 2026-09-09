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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpServerHydrator;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServerUserPermissions;

/**
 * @internal
 */
final class McpServerHydratorTest extends Unit
{
    public function testHydrateMapsDefinitionGridAndResolvedPermissions(): void
    {
        $definition = new McpServerDefinition(
            id: 'product-read',
            displayName: 'Product (read-only)',
            description: 'Read access to product data',
            urlSlug: 'product-read',
            toolIds: ['get_car_info', 'search_data_objects'],
            scopes: ['mcp:read'],
            enabled: true,
            access: new McpServerAccess(
                owner: 'john.doe',
                shareGlobal: false,
                sharedUsers: [new McpServerAccessEntry('alice', canAccess: true, canEdit: true)],
                sharedRoles: [new McpServerAccessEntry('editors', canAccess: false, canEdit: true)],
            ),
        );

        $server = (new McpServerHydrator())->hydrate(
            $definition,
            'https://host/pimcore-mcp/studio/product-read',
            ['mcp:read'],
            true,
            new McpServerUserPermissions(canView: true, canAccess: false, canEdit: true),
        );

        $this->assertSame('product-read', $server->getId());
        $this->assertSame('john.doe', $server->getOwner());
        $this->assertFalse($server->isShareGlobal());
        $this->assertTrue($server->isWriteable());
        $this->assertSame(2, $server->getToolCount());

        $users = $server->getSharedUsers();
        $this->assertCount(1, $users);
        $this->assertSame('alice', $users[0]->getName());
        $this->assertTrue($users[0]->isCanAccess());
        $this->assertTrue($users[0]->isCanEdit());

        $roles = $server->getSharedRoles();
        $this->assertSame('editors', $roles[0]->getName());
        $this->assertFalse($roles[0]->isCanAccess());
        $this->assertTrue($roles[0]->isCanEdit());

        $this->assertTrue($server->getCurrentUserPermissions()->isCanView());
        $this->assertFalse($server->getCurrentUserPermissions()->isCanAccess());
        $this->assertTrue($server->getCurrentUserPermissions()->isCanEdit());
    }

    public function testHydrateNormalisesEmptyDescriptionAndEmptyGrid(): void
    {
        $definition = new McpServerDefinition(
            id: 'x',
            displayName: 'X',
            description: '',
            urlSlug: 'x',
            toolIds: [],
            scopes: [],
            enabled: false,
            access: new McpServerAccess(),
        );

        $server = (new McpServerHydrator())->hydrate(
            $definition,
            'https://host/pimcore-mcp/studio/x',
            [],
            false,
            new McpServerUserPermissions(canView: false, canAccess: false, canEdit: false),
        );

        $this->assertNull($server->getDescription());
        $this->assertNull($server->getOwner());
        $this->assertSame([], $server->getSharedUsers());
        $this->assertSame([], $server->getSharedRoles());
        $this->assertFalse($server->getCurrentUserPermissions()->isCanView());
        $this->assertSame(0, $server->getToolCount());
    }
}
