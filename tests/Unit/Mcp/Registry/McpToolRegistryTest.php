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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Registry;

use Codeception\Test\Unit;
use Mcp\Schema\ToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolReference;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class McpToolRegistryTest extends Unit
{
    public function testExposesToolReferencesRebuiltFromMetadata(): void
    {
        $registry = new McpToolRegistry([
            'get_thing' => [
                'class' => stdClass::class, 'method' => 'execute', 'title' => 'Get Thing',
                'description' => 'reads a thing', 'annotations' => ['readOnlyHint' => true], 'outputSchema' => null,
            ],
            'delete_thing' => [
                'class' => stdClass::class, 'method' => 'run', 'title' => null,
                'description' => '', 'annotations' => null, 'outputSchema' => null,
            ],
        ]);

        $this->assertSame(['get_thing', 'delete_thing'], $registry->names());
        $this->assertTrue($registry->has('get_thing'));
        $this->assertFalse($registry->has('missing'));
        $this->assertNull($registry->get('missing'));
        $this->assertCount(2, $registry->all());

        $ref = $registry->get('get_thing');
        $this->assertInstanceOf(McpToolReference::class, $ref);
        $this->assertSame('get_thing', $ref->name);
        $this->assertSame('Get Thing', $ref->title);
        $this->assertSame(stdClass::class, $ref->className);
        $this->assertSame('execute', $ref->method);
        $this->assertInstanceOf(ToolAnnotations::class, $ref->annotations);
        $this->assertTrue($ref->isReadOnly());
        $this->assertFalse($ref->isDestructive());

        $writeRef = $registry->get('delete_thing');
        $this->assertNull($writeRef->title);
        $this->assertNull($writeRef->annotations);
        $this->assertFalse($writeRef->isReadOnly());
    }

    public function testEmptyRegistry(): void
    {
        $registry = new McpToolRegistry();

        $this->assertSame([], $registry->all());
        $this->assertSame([], $registry->names());
    }

    public function testGetLocatorFallsBackToAnEmptyContainer(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, (new McpToolRegistry())->getLocator());
    }

    public function testGetLocatorReturnsTheInjectedOne(): void
    {
        $locator = new ServiceLocator([]);

        $this->assertSame($locator, (new McpToolRegistry([], $locator))->getLocator());
    }
}
