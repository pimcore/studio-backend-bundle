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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection\Fixture;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

/**
 * A tool whose service is registered under an id that is not its class name, as
 * `app.custom_tool: { class: CustomServiceIdMcpTool }` would.
 */
final class CustomServiceIdMcpTool
{
    #[McpTool(name: 'custom_id_tool', description: 'Explicit description.')]
    public function execute(): CallToolResult
    {
        return new CallToolResult([new TextContent('ok')]);
    }
}
