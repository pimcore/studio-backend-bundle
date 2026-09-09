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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccessEntry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepository;
use Pimcore\Config\LocationAwareConfigRepository;

final class McpServerConfigRepositoryTest extends Unit
{
    /**
     * In-memory {@see LocationAwareConfigRepository} standing in for the real
     * settings-store/YAML backend, so the repository orchestration is unit-testable.
     *
     * @param array<string, array<string, mixed>> $seed
     */
    private function inMemoryStore(array $seed = [], bool $writeable = true): LocationAwareConfigRepository
    {
        return new class($seed, $writeable) extends LocationAwareConfigRepository {
            /** @param array<string, array<string, mixed>> $data */
            public function __construct(public array $data, private readonly bool $writeable)
            {
                // Intentionally does not call parent::__construct — every method
                // the repository touches is overridden below.
            }

            public function loadConfigByKey(string $key): array
            {
                return [$this->data[$key] ?? null, 'settings-store'];
            }

            public function fetchAllKeys(): array
            {
                return array_keys($this->data);
            }

            public function saveConfig(string $key, mixed $data, ?callable $yamlStructureCallback = null): void
            {
                $this->data[$key] = $data;
            }

            public function deleteData(string $key, ?string $dataSource): void
            {
                unset($this->data[$key]);
            }

            public function getWriteTarget(): string
            {
                return 'settings-store';
            }

            public function isWriteable(?string $key = null, ?string $dataSource = null): bool
            {
                return $this->writeable;
            }
        };
    }

    private function repository(LocationAwareConfigRepository $store): McpServerConfigRepository
    {
        return new McpServerConfigRepository([], [], $store);
    }

    public function testGetReadsAndMapsAServer(): void
    {
        $store = $this->inMemoryStore([
            'objects-read' => ['name' => 'Objects', 'tools' => ['get_data_object'], 'scopes' => ['mcp:read']],
        ]);

        $server = $this->repository($store)->get('objects-read');

        $this->assertSame('objects-read', $server->id);
        $this->assertSame('Objects', $server->displayName);
        $this->assertSame(['get_data_object'], $server->toolIds);
    }

    public function testGetThrowsNotFoundForUnknownId(): void
    {
        $this->expectException(NotFoundException::class);

        $this->repository($this->inMemoryStore())->get('missing');
    }

    public function testHasReflectsPresence(): void
    {
        $repository = $this->repository($this->inMemoryStore(['a' => ['name' => 'A']]));

        $this->assertTrue($repository->has('a'));
        $this->assertFalse($repository->has('b'));
    }

    public function testListMapsAllServers(): void
    {
        $store = $this->inMemoryStore([
            'a' => ['name' => 'A'],
            'b' => ['name' => 'B'],
        ]);

        $servers = $this->repository($store)->list();

        $this->assertCount(2, $servers);
        $this->assertSame(['a', 'b'], array_map(static fn (McpServerDefinition $s): string => $s->id, $servers));
    }

    public function testSaveThenGetRoundTrip(): void
    {
        $store = $this->inMemoryStore();
        $repository = $this->repository($store);

        $server = new McpServerDefinition(
            id: 'assets-read',
            displayName: 'Assets (read)',
            description: 'Read-only asset tools',
            urlSlug: 'assets-read',
            toolIds: ['get_asset'],
            scopes: ['mcp:read'],
            enabled: true,
            access: new McpServerAccess(
                owner: 'jane.doe',
                sharedRoles: [new McpServerAccessEntry('editors', canAccess: true, canEdit: true)]
            ),
        );
        $repository->save($server);

        $this->assertEquals($server, $repository->get('assets-read'));
    }

    public function testSaveThrowsWhenStorageIsReadOnly(): void
    {
        $repository = $this->repository($this->inMemoryStore(writeable: false));

        $this->expectException(NotWriteableException::class);

        $repository->save(new McpServerDefinition(
            id: 'x',
            displayName: 'X',
            description: '',
            urlSlug: 'x',
            toolIds: [],
            scopes: [],
            enabled: true,
            access: new McpServerAccess(),
        ));
    }

    public function testDeleteRemovesAndThenReportsAbsent(): void
    {
        $store = $this->inMemoryStore(['gone' => ['name' => 'Gone']]);
        $repository = $this->repository($store);

        $repository->delete('gone');

        $this->assertFalse($repository->has('gone'));
    }

    public function testDeleteThrowsNotFoundForUnknownId(): void
    {
        $this->expectException(NotFoundException::class);

        $this->repository($this->inMemoryStore())->delete('missing');
    }
}
