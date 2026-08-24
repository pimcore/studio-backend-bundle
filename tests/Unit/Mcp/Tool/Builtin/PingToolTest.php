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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Tool\Builtin;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\Builtin\PingTool;

final class PingToolTest extends Unit
{
    public function testDefinitionIsReadOnlyAndNamedPing(): void
    {
        $definition = (new PingTool())->getDefinition();

        $this->assertSame('ping', $definition->name);
        $this->assertTrue($definition->annotations->readOnly);
        $this->assertSame(McpScopes::READ, $definition->requiredScope());
    }

    public function testExecuteReturnsPong(): void
    {
        $result = (new PingTool())->execute([]);

        $this->assertSame('pong', $result->text);
        $this->assertFalse($result->isError);
    }
}
