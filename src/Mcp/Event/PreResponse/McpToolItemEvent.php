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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpToolItem;

final class McpToolItemEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.mcp_tool_item';

    public function __construct(
        private readonly McpToolItem $tool
    ) {
        parent::__construct($tool);
    }

    public function getTool(): McpToolItem
    {
        return $this->tool;
    }
}
