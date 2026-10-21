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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Repository;

use Codeception\Test\Unit;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Repository\RelatedElementRepository;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;

/**
 * @internal
 */
final class RelatedElementRepositoryTest extends Unit
{
    private MockObject|Connection $connection;

    private RelatedElementRepository $repository;

    protected function _before(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $dbResolver = $this->createMock(DbResolverInterface::class);
        $dbResolver->method('get')->willReturn($this->connection);
        $this->repository = new RelatedElementRepository($dbResolver);
    }

    public function testDataObjectsAreReadWithOneBoundQuery(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->stringContains('FROM objects WHERE id IN (:ids)'),
                ['ids' => [5, 6, 7, 1]],
                ['ids' => ArrayParameterType::INTEGER]
            )
            ->willReturn([
                ['id' => '5', 'parentId' => '7', 'path' => '/p/', 'filename' => 'a', 'type' => 'object', 'className' => 'Product', 'published' => '1'],
                ['id' => '6', 'parentId' => '5', 'path' => '/p/a/', 'filename' => 'v', 'type' => 'variant', 'className' => 'Product', 'published' => '0'],
                ['id' => '7', 'parentId' => '1', 'path' => '/', 'filename' => 'p', 'type' => 'folder', 'className' => 'Product', 'published' => '1'],
                ['id' => '1', 'parentId' => '0', 'path' => '/', 'filename' => '', 'type' => 'folder', 'className' => null, 'published' => '1'],
            ]);

        $result = $this->repository->getRelatedElementSummaries('object', [5, 6, 7, 5, 1]);

        $this->assertSame(
            ['id' => 5, 'parentId' => 7, 'type' => 'object', 'subtype' => 'object', 'className' => 'Product', 'fullpath' => '/p/a', 'filename' => 'a', 'published' => true],
            $result[5]
        );
        $this->assertSame('variant', $result[6]['subtype']);
        $this->assertSame('Product', $result[6]['className']);
        $this->assertFalse($result[6]['published']);
        $this->assertNull($result[7]['className'], 'folders have no class name');
        $this->assertSame('/p', $result[7]['fullpath']);
        $this->assertSame('/', $result[1]['fullpath'], 'object root');
    }

    public function testAssetsAreAlwaysPublishedAndHaveNoClassName(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->stringContains('FROM assets WHERE id IN (:ids)'))
            ->willReturn([
                ['id' => '3', 'parentId' => '2', 'path' => '/img/', 'filename' => 'a.jpg', 'type' => 'image', 'className' => null, 'published' => '1'],
            ]);

        $this->assertSame(
            ['id' => 3, 'parentId' => 2, 'type' => 'asset', 'subtype' => 'image', 'className' => null, 'fullpath' => '/img/a.jpg', 'filename' => 'a.jpg', 'published' => true],
            $this->repository->getRelatedElementSummaries('asset', [3])[3]
        );
    }

    public function testDocumentsKeepTheirPublishedFlag(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->stringContains('FROM documents WHERE id IN (:ids)'))
            ->willReturn([
                ['id' => '9', 'parentId' => '1', 'path' => '/en/', 'filename' => 'home', 'type' => 'page', 'className' => null, 'published' => '0'],
            ]);

        $summary = $this->repository->getRelatedElementSummaries('document', [9])[9];

        $this->assertSame('page', $summary['subtype']);
        $this->assertSame('/en/home', $summary['fullpath']);
        $this->assertFalse($summary['published']);
    }

    public function testNoQueryForEmptyIdsOrUnknownTypes(): void
    {
        $this->connection->expects($this->never())->method('fetchAllAssociative');

        $this->assertSame([], $this->repository->getRelatedElementSummaries('object', []));
        $this->assertSame([], $this->repository->getRelatedElementSummaries('unknown', [1]));
    }

    public function testDatabaseErrorsAreWrapped(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willThrowException($this->createMock(DBALException::class));

        $this->expectException(DatabaseException::class);

        $this->repository->getRelatedElementSummaries('object', [1]);
    }
}
