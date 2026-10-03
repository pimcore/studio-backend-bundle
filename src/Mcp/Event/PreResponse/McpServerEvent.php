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
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;

final class McpServerEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.mcp_server';

    public function __construct(
        private readonly McpServer $server
    ) {
        parent::__construct($server);
    }

    public function getServer(): McpServer
    {
        return $this->server;
    }
}
