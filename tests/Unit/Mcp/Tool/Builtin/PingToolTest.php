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
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\Builtin\PingTool;

final class PingToolTest extends Unit
{
    public function testExecuteReturnsPong(): void
    {
        $result = (new PingTool())->execute();

        $this->assertInstanceOf(CallToolResult::class, $result);
        $this->assertFalse($result->isError);

        $content = $result->content[0];
        $this->assertInstanceOf(TextContent::class, $content);
        $this->assertSame('pong', $content->text);
    }
}
