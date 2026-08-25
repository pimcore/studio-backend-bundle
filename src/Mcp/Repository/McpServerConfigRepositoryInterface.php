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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;

/**
 * Reads and writes MCP server definitions through the location-aware config
 * repository (shipped symfony-config seed + runtime settings-store), keyed by
 * server id.
 *
 * @internal
 */
interface McpServerConfigRepositoryInterface
{
    /**
     * @return list<McpServerDefinition>
     *
     * @throws NotFoundException
     */
    public function list(): array;

    public function has(string $id): bool;

    /**
     * Whether the configured storage target accepts writes (false e.g. for a
     * symfony-config target outside debug mode).
     */
    public function isWriteable(): bool;

    /**
     * @throws NotFoundException when no server with that id exists
     */
    public function get(string $id): McpServerDefinition;

    /**
     * @throws NotWriteableException when the configured storage target is read-only
     */
    public function save(McpServerDefinition $server): void;

    /**
     * @throws NotFoundException     when no server with that id exists
     * @throws NotWriteableException when the configured storage target is read-only
     */
    public function delete(string $id): void;
}
