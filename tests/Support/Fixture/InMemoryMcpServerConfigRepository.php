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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use function array_values;
use function in_array;

/**
 * MCP server definitions kept in memory; ids listed as read-only refuse writes like a
 * shipped symfony-config server does.
 *
 * @internal
 */
final class InMemoryMcpServerConfigRepository implements McpServerConfigRepositoryInterface
{
    /**
     * @var array<string, McpServerDefinition>
     */
    private array $servers = [];

    /**
     * @param list<McpServerDefinition> $servers
     * @param list<string> $readOnlyIds
     */
    public function __construct(array $servers = [], private readonly array $readOnlyIds = [])
    {
        foreach ($servers as $server) {
            $this->servers[$server->id] = $server;
        }
    }

    public function list(): array
    {
        return array_values($this->servers);
    }

    public function has(string $id): bool
    {
        return isset($this->servers[$id]);
    }

    public function isWriteable(?string $id = null): bool
    {
        return $id === null || !in_array($id, $this->readOnlyIds, true);
    }

    public function get(string $id): McpServerDefinition
    {
        return $this->servers[$id] ?? throw new NotFoundException('MCP server', $id);
    }

    public function save(McpServerDefinition $server): void
    {
        if (!$this->isWriteable($server->id)) {
            throw new NotWriteableException('MCP server');
        }

        $this->servers[$server->id] = $server;
    }

    public function delete(string $id): void
    {
        unset($this->servers[$id]);
    }
}
