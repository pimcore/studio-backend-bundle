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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Repository;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepository;

/**
 * @internal
 */
final class WorkflowElementsRepositoryTest extends Unit
{
    private MockObject|DbResolverInterface $dbResolver;

    private MockObject|Connection $connection;

    private WorkflowElementsRepository $repository;

    protected function _before(): void
    {
        $this->dbResolver = $this->createMock(DbResolverInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new WorkflowElementsRepository($this->dbResolver);
    }

    public function testFetchByWorkflowStateExcludesFolders(): void
    {
        $wheres = [];
        $queryBuilder = $this->createMock(QueryBuilder::class);
        foreach (['select', 'addSelect', 'from', 'where', 'setParameter', 'orderBy', 'addOrderBy'] as $method) {
            $queryBuilder->method($method)->willReturnSelf();
        }
        // The asset/object/document subtype tables are joined so their type can be inspected.
        $queryBuilder->expects($this->exactly(3))->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnCallback(
            function (string $condition) use (&$wheres, $queryBuilder) {
                $wheres[] = $condition;

                return $queryBuilder;
            }
        );
        $queryBuilder->method('fetchAllAssociative')->willReturn([]);

        $this->connection->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->dbResolver->method('get')->willReturn($this->connection);

        $this->repository->fetchByWorkflowState('product_workflow');

        // Folder rows are filtered out in SQL for every element type. The IS NULL guard is
        // essential: a plain "type != 'folder'" would drop every non-matching LEFT JOIN row
        // (NULL type) via three-valued logic, emptying the result.
        $this->assertContains("(a.type IS NULL OR a.type != 'folder')", $wheres);
        $this->assertContains("(o.type IS NULL OR o.type != 'folder')", $wheres);
        $this->assertContains("(d.type IS NULL OR d.type != 'folder')", $wheres);
    }
}
