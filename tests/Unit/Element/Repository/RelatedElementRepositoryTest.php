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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
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
                ['ids' => [5, 6, 7]],
                ['ids' => ArrayParameterType::INTEGER]
            )
            ->willReturn([
                ['id' => '5', 'type' => 'object', 'subtype' => 'Product', 'published' => '1', 'fullPath' => '/p/a'],
                ['id' => '6', 'type' => 'variant', 'subtype' => 'Product', 'published' => '0', 'fullPath' => '/p/a/v'],
                ['id' => '7', 'type' => 'folder', 'subtype' => null, 'published' => '1', 'fullPath' => '/p'],
            ]);

        $result = $this->repository->getRelatedElementsData('object', [5, 6, 7, 5]);

        $this->assertRelatedElement($result[5], 5, 'object', 'Product', '/p/a', true);
        $this->assertRelatedElement($result[6], 6, 'object', 'Product', '/p/a/v', false);
        $this->assertRelatedElement($result[7], 7, 'object', 'folder', '/p', null);
    }

    public function testAssetsUseTheirTypeAsSubtypeAndHaveNoPublishedFlag(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->stringContains('FROM assets WHERE id IN (:ids)'))
            ->willReturn([
                ['id' => '3', 'type' => 'image', 'subtype' => 'image', 'published' => null, 'fullPath' => '/img/a.jpg'],
            ]);

        $result = $this->repository->getRelatedElementsData('asset', [3]);

        $this->assertRelatedElement($result[3], 3, 'asset', 'image', '/img/a.jpg', null);
    }

    public function testDocumentsUseTheirTypeAsSubtypeAndPublishedFlag(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->stringContains('FROM documents WHERE id IN (:ids)'))
            ->willReturn([
                ['id' => '9', 'type' => 'page', 'subtype' => 'page', 'published' => '0', 'fullPath' => '/en/home'],
            ]);

        $result = $this->repository->getRelatedElementsData('document', [9]);

        $this->assertRelatedElement($result[9], 9, 'document', 'page', '/en/home', false);
    }

    public function testNoQueryForEmptyIdsOrUnknownTypes(): void
    {
        $this->connection->expects($this->never())->method('fetchAllAssociative');

        $this->assertSame([], $this->repository->getRelatedElementsData('object', []));
        $this->assertSame([], $this->repository->getRelatedElementsData('unknown', [1]));
    }

    public function testDatabaseErrorsAreWrapped(): void
    {
        $this->connection->method('fetchAllAssociative')
            ->willThrowException($this->createMock(DBALException::class));

        $this->expectException(DatabaseException::class);

        $this->repository->getRelatedElementsData('object', [1]);
    }

    private function assertRelatedElement(
        RelatedElementData $element,
        int $id,
        string $type,
        string $subtype,
        string $fullPath,
        ?bool $published
    ): void {
        $this->assertSame($id, $element->getId());
        $this->assertSame($type, $element->getType());
        $this->assertSame($subtype, $element->getSubtype());
        $this->assertSame($fullPath, $element->getFullPath());
        $this->assertSame($published, $element->getIsPublished());
    }
}
