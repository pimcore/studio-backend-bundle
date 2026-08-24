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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\DuplicateMcpToolException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolResult;

final class McpToolRegistryTest extends Unit
{
    private function tool(string $name): McpToolInterface
    {
        return new class($name) implements McpToolInterface {
            public function __construct(private readonly string $name)
            {
            }

            public function getDefinition(): McpToolDefinition
            {
                return new McpToolDefinition($this->name, $this->name, 'desc', new McpToolAnnotations(readOnly: true));
            }

            public function execute(array $arguments): McpToolResult
            {
                return McpToolResult::text('ok');
            }
        };
    }

    public function testIndexesToolsByName(): void
    {
        $a = $this->tool('a');
        $b = $this->tool('b');
        $registry = new McpToolRegistry([$a, $b]);

        $this->assertSame(['a', 'b'], $registry->names());
        $this->assertSame([$a, $b], $registry->all());
        $this->assertTrue($registry->has('a'));
        $this->assertFalse($registry->has('missing'));
        $this->assertSame($b, $registry->get('b'));
        $this->assertNull($registry->get('missing'));
    }

    public function testEmptyRegistry(): void
    {
        $registry = new McpToolRegistry();

        $this->assertSame([], $registry->all());
        $this->assertSame([], $registry->names());
    }

    public function testDuplicateToolNameThrows(): void
    {
        $this->expectException(DuplicateMcpToolException::class);

        new McpToolRegistry([$this->tool('dup'), $this->tool('dup')]);
    }
}
