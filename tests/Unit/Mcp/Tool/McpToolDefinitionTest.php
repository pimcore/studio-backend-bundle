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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Tool;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolAnnotations;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolDefinition;

final class McpToolDefinitionTest extends Unit
{
    public function testReadOnlyToolRequiresReadScope(): void
    {
        $definition = $this->definition(new McpToolAnnotations(readOnly: true));

        $this->assertSame(McpScopes::READ, $definition->requiredScope());
    }

    public function testNonReadOnlyToolRequiresWriteScope(): void
    {
        $definition = $this->definition(new McpToolAnnotations(readOnly: false, destructive: true));

        $this->assertSame(McpScopes::WRITE, $definition->requiredScope());
    }

    public function testUnannotatedToolDefaultsToWriteScope(): void
    {
        // Fail-safe: a tool that declares nothing is treated as a write.
        $definition = $this->definition(new McpToolAnnotations());

        $this->assertSame(McpScopes::WRITE, $definition->requiredScope());
    }

    public function testAnnotationsMapToTheMcpWireHintKeys(): void
    {
        $annotations = new McpToolAnnotations(readOnly: true, destructive: false, idempotent: true, openWorld: false);

        $this->assertSame([
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ], $annotations->toArray());
    }

    public function testDefaultInputSchemaIsAnObject(): void
    {
        $this->assertSame(['type' => 'object'], $this->definition(new McpToolAnnotations())->inputSchema);
    }

    private function definition(McpToolAnnotations $annotations): McpToolDefinition
    {
        return new McpToolDefinition('name', 'Title', 'Description', $annotations);
    }
}
