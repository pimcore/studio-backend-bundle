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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Note\Service;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\FilterService;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\FilterServiceInterface;
use Pimcore\Model\Element\Note\Listing as NoteListing;

final class FilterServiceTest extends Unit
{
    private FilterServiceInterface $filterService;

    public function _before(): void
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => function (string $identifier): string {
                return '`' . str_replace('`', '``', $identifier) . '`';
            },
        ]);
        $dbResolver = $this->makeEmpty(DbResolverInterface::class, [
            'get' => $connection,
        ]);
        $this->filterService = new FilterService($dbResolver);
    }

    public function testApplyFilter(): void
    {
        $noteListing = $this->getNoteListing();
        $noteParameters = new NoteParameters(
            filter: 'test'
        );
        $this->filterService->applyFilter($noteListing, $noteParameters);

        $this->assertSame(
            "((`title` LIKE :filter OR `description` LIKE :filter OR `type` LIKE :filter OR `user` IN (SELECT `id` FROM `users` WHERE `name` LIKE :filter) OR DATE_FORMAT(FROM_UNIXTIME(`date`), '%Y-%m-%d') LIKE :filter)) ",
            $noteListing->getCondition());

        $this->assertSame(
            ['filter' => '%test%'],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * @throws JsonException
     */
    public function testApplyFieldFiltersDate(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'field' => 'date',
                'type' => 'date',
                'operator' => 'eq',
                'value' => '05/04/2024',
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame('(`date` BETWEEN :minTime_0 AND :maxTime_0) ', $noteListing->getCondition());
        $this->assertSame(
            [
                'minTime_0' => 1714780800,
                'maxTime_0' => 1714867199,
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * @throws JsonException
     */
    public function testApplyFieldFiltersNumeric(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'field' => 'numeric',
                'type' => 'numeric',
                'operator' => 'eq',
                'value' => 10,
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame('(`numeric` = :filter_0) ', $noteListing->getCondition());
        $this->assertSame(
            [
                'filter_0' => 10,
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * @throws JsonException
     */
    public function testApplyFieldFiltersBoolean(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'field' => 'boolean',
                'type' => 'boolean',
                'operator' => 'boolean',
                'value' => true,
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame('(`boolean` = :filter_0) ', $noteListing->getCondition());
        $this->assertSame(
            [
                'filter_0' => 1,
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * @throws JsonException
     */
    public function testApplyFieldFiltersList(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'field' => 'list',
                'type' => 'list',
                'operator' => 'list',
                'value' => 'list',
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame('(`list` = :filter_0) ', $noteListing->getCondition());
        $this->assertSame(
            [
                'filter_0' => 'list',
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * The Studio UI sends the user column as `userName` (matching the Note schema) with a
     * string `like` operator. It must be translated into a subquery against `users`.`name`
     * and must NOT emit a bogus `` `userName` `` column condition (there is no such column).
     *
     * @throws JsonException
     */
    public function testApplyFieldFiltersUser(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'field' => 'userName',
                'type' => 'string',
                'operator' => 'like',
                'value' => 'admin',
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame(
            '(`user` IN (SELECT `id` FROM `users` WHERE `name` LIKE :filter_0)) ',
            $noteListing->getCondition()
        );
        $this->assertSame(
            [
                'filter_0' => '%admin%',
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * Regression test for the "Between" date operator (#1923). The UI decomposes a
     * between-range into two conditions on the same `date` field (gt from + lt to).
     * The bind parameters must be unique per condition, otherwise the second value
     * overwrites the first and the query collapses to `date > to AND date < to`.
     *
     * @throws JsonException
     */
    public function testApplyFieldFiltersDateBetween(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'field' => 'date',
                'type' => 'date',
                'operator' => 'gt',
                'value' => '05/04/2024',
            ],
            [
                'field' => 'date',
                'type' => 'date',
                'operator' => 'lt',
                'value' => '05/06/2024',
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame(
            '(`date` > :filter_0)  AND (`date` < :filter_1) ',
            $noteListing->getCondition()
        );
        $this->assertSame(
            [
                'filter_0' => 1714780800,
                'filter_1' => 1714953600,
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * @throws JsonException
     */
    public function testApplyFieldFiltersInvalidJson(): void
    {
        $noteListing = $this->getNoteListing();
        $filters = json_encode([
            [
                'invalidKey' => 'invalidValue',
            ],
        ], JSON_THROW_ON_ERROR);
        $noteParameters = new NoteParameters(fieldFilters: $filters);

        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('Invalid filter: fieldFilters');

        $this->filterService->applyFieldFilters($noteListing, $noteParameters);
    }

    private function getNoteListing(): NoteListing
    {
        return new NoteListing();
    }
}
