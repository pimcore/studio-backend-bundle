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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Config\LocationAwareConfigRepository;
use function sprintf;

/**
 * Location-aware storage for MCP server definitions, mirroring
 * {@see \Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\PerspectiveConfigRepository}:
 * shipped defaults come from the `studio_mcp_servers` symfony-config node, and
 * runtime servers from the configured write target (settings-store by default).
 *
 * @internal
 */
final class McpServerConfigRepository implements McpServerConfigRepositoryInterface
{
    private ?LocationAwareConfigRepository $repository;

    /**
     * @param array<string, mixed>            $serverConfigurations shipped server seed (the studio_mcp_servers node)
     * @param array<string, mixed>            $storageConfig        the config_location.studio_mcp_servers subtree
     * @param LocationAwareConfigRepository|null $repository        pre-built backend; null in production (built lazily),
     *                                                              injected as an in-memory backend by tests
     */
    public function __construct(
        private readonly array $serverConfigurations,
        private readonly array $storageConfig,
        ?LocationAwareConfigRepository $repository = null,
    ) {
        $this->repository = $repository;
    }

    public function list(): array
    {
        $servers = [];
        foreach ($this->getRepository()->fetchAllKeys() as $id) {
            $servers[] = $this->get((string) $id);
        }

        return $servers;
    }

    public function has(string $id): bool
    {
        return $this->getRepository()->loadConfigByKey($id)[0] !== null;
    }

    public function isWriteable(): bool
    {
        try {
            return $this->getRepository()->isWriteable();
        } catch (Exception) {
            return false;
        }
    }

    public function get(string $id): McpServerDefinition
    {
        [$data] = $this->getRepository()->loadConfigByKey($id);
        if ($data === null) {
            throw new NotFoundException('MCP server', $id);
        }

        return McpServerDefinition::fromArray($id, $data);
    }

    public function save(McpServerDefinition $server): void
    {
        $this->assertWriteable();

        try {
            $this->getRepository()->saveConfig(
                $server->id,
                $server->toArray(),
                static fn (string $key, mixed $data): array => [
                    Configuration::ROOT_NODE => [
                        Configuration::MCP_SERVERS_NODE => [
                            $key => $data,
                        ],
                    ],
                ]
            );
        } catch (Exception $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }
    }

    public function delete(string $id): void
    {
        if (!$this->has($id)) {
            throw new NotFoundException('MCP server', $id);
        }

        try {
            $repository = $this->getRepository();
            $repository->deleteData($id, $repository->getWriteTarget());
        } catch (Exception $exception) {
            throw new NotWriteableException(
                'mcp_server',
                sprintf('MCP server "%s" could not be deleted: %s', $id, $exception->getMessage()),
                $exception
            );
        }
    }

    private function getRepository(): LocationAwareConfigRepository
    {
        return $this->repository ??= new LocationAwareConfigRepository(
            $this->serverConfigurations,
            Configuration::MCP_SERVERS_NODE,
            $this->storageConfig,
        );
    }

    /**
     * @throws NotWriteableException
     */
    private function assertWriteable(): void
    {
        try {
            if (!$this->getRepository()->isWriteable()) {
                throw new NotWriteableException(
                    'mcp_server',
                    'The MCP server configuration storage is not writeable.'
                );
            }
        } catch (NotWriteableException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new NotWriteableException(
                'mcp_server',
                sprintf('The MCP server configuration could not be written: %s', $exception->getMessage()),
                $exception
            );
        }
    }
}
