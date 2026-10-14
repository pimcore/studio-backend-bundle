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
 * Tools that leave the attribute's description out, so the DocBlock has to supply it.
 */
final class DocBlockDescribedMcpTool
{
    /**
     * Returns the greeting.
     *
     * Longer explanation of what the greeting is for.
     */
    #[McpTool(name: 'doc_tool')]
    public function described(): CallToolResult
    {
        return new CallToolResult([new TextContent('hello')]);
    }

    #[McpTool(name: 'undescribed_tool')]
    public function undescribed(): CallToolResult
    {
        return new CallToolResult([new TextContent('hello')]);
    }
}
