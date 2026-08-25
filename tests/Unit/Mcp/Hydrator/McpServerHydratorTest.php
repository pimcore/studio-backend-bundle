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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Hydrator\McpServerHydrator;

/**
 * @internal
 */
final class McpServerHydratorTest extends Unit
{
    public function testHydrateMapsDefinitionAccessAndDerivedValues(): void
    {
        $definition = new McpServerDefinition(
            id: 'product-read',
            displayName: 'Product (read-only)',
            description: 'Read access to product data',
            urlSlug: 'product-read',
            toolIds: ['get_car_info', 'search_data_objects'],
            scopes: ['mcp:read'],
            enabled: true,
            access: new McpServerAccess(owner: 42, shareGlobal: false, sharedUsers: [7], sharedRoles: [3]),
        );

        $server = (new McpServerHydrator())->hydrate(
            $definition,
            'https://host/pimcore-mcp/studio/product-read',
            ['mcp:read'],
            true,
        );

        $this->assertSame('product-read', $server->getId());
        $this->assertSame('Product (read-only)', $server->getName());
        $this->assertSame('Read access to product data', $server->getDescription());
        $this->assertSame('product-read', $server->getUrlSlug());
        $this->assertSame('https://host/pimcore-mcp/studio/product-read', $server->getUrl());
        $this->assertSame(['get_car_info', 'search_data_objects'], $server->getTools());
        $this->assertSame(['mcp:read'], $server->getScopes());
        $this->assertTrue($server->isEnabled());
        $this->assertSame(42, $server->getOwnerId());
        $this->assertFalse($server->isShareGlobal());
        $this->assertSame([7], $server->getSharedUsers());
        $this->assertSame([3], $server->getSharedRoles());
        $this->assertTrue($server->isWriteable());
        $this->assertSame(2, $server->getToolCount());
    }

    public function testHydrateNormalisesEmptyDescriptionToNull(): void
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

        $server = (new McpServerHydrator())->hydrate($definition, 'https://host/pimcore-mcp/studio/x', [], false);

        $this->assertNull($server->getDescription());
        $this->assertNull($server->getOwnerId());
        $this->assertFalse($server->isEnabled());
        $this->assertFalse($server->isWriteable());
        $this->assertSame(0, $server->getToolCount());
    }
}
