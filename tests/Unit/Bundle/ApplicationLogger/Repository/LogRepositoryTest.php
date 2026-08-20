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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\ApplicationLogger\Repository;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Repository\LogRepository;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Util\Constant\SortableFields;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;

/**
 * @internal
 */
final class LogRepositoryTest extends Unit
{
    public function testListSortsByEveryAllowedField(): void
    {
        foreach (SortableFields::cases() as $field) {
            $sql = $this->captureListSql(new SortFilter(key: $field->value, direction: 'ASC'));

            $this->assertStringContainsString(
                'ORDER BY `' . $field->value . '` ' . SortDirection::ASC->value,
                $sql
            );
        }
    }

    /**
     * An unmapped grid column id used to reach the ORDER BY clause verbatim, which failed
     * the query with "Unknown column ... in ORDER BY" and surfaced as a 500 response.
     */
    public function testListFallsBackToDefaultForUnknownSortKey(): void
    {
        $sql = $this->captureListSql(new SortFilter(key: 'relatedElementData', direction: 'DESC'));

        $this->assertStringContainsString(
            'ORDER BY `' . SortableFields::ID->value . '` ' . SortDirection::DESC->value,
            $sql
        );
        $this->assertStringNotContainsString('relatedElementData', $sql);
    }

    public function testListIgnoresLocaleSuffixOnSortKey(): void
    {
        $sql = $this->captureListSql(new SortFilter(key: 'timestamp', direction: 'ASC', locale: 'de'));

        $this->assertStringContainsString('ORDER BY `timestamp` ' . SortDirection::ASC->value, $sql);
    }

    public function testListWithoutFiltersSortsByDefault(): void
    {
        $sql = $this->captureListSql(null);

        $this->assertStringContainsString(
            'ORDER BY ' . SortableFields::ID->value . ' DESC',
            $sql
        );
    }

    private function captureListSql(?SortFilter $sortFilter): string
    {
        $capturedSql = '';
        $result = $this->makeEmpty(Result::class, [
            'fetchAllAssociative' => [],
        ]);

        $connection = null;
        $connection = $this->makeEmpty(Connection::class, [
            // The query builder renders its SQL through the platform, so a real one is
            // needed for the ORDER BY clause to show up at all.
            'getDatabasePlatform' => new MySQL84Platform(),
            'createQueryBuilder' => static function () use (&$connection): QueryBuilder {
                return new QueryBuilder($connection);
            },
            'quoteIdentifier' => static function (string $identifier): string {
                return '`' . $identifier . '`';
            },
            'executeQuery' => static function (string $sql) use ($result, &$capturedSql): Result {
                $capturedSql = $sql;

                return $result;
            },
        ]);

        $repository = new LogRepository(
            $this->makeEmpty(DbResolverInterface::class, [
                'get' => $connection,
            ])
        );

        $repository->list(
            new CollectionFilterParameter(
                $sortFilter === null ? null : new FilterParameter(sortFilter: $sortFilter)
            )
        );

        return $capturedSql;
    }
}
