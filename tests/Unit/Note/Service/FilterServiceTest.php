<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Note\Service;

use Codeception\Test\Unit;
use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Note\Request\NoteParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\FilterService;
use Pimcore\Bundle\StudioBackendBundle\Note\Service\FilterServiceInterface;
use Pimcore\Model\Element\Note\Listing as NoteListing;

final class FilterServiceTest extends Unit
{
    private FilterServiceInterface $filterService;
    public function _before(): void
    {
        $this->filterService = new FilterService();
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
        $noteParameters = new NoteParameters(
            fieldFilters: json_encode([
                [
                    'field' => 'date',
                    'type' => 'date',
                    'operator' => 'eq',
                    'value' => '05/04/2024'
                ]
            ], JSON_THROW_ON_ERROR)
        );
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame("(`date`  BETWEEN :minTime AND :maxTime) ", $noteListing->getCondition());
        $this->assertSame(
            [
                'minTime' => 1714780800,
                'maxTime' => 1714867199
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
        $noteParameters = new NoteParameters(
            fieldFilters: json_encode([
                [
                    'field' => 'numeric',
                    'type' => 'numeric',
                    'operator' => 'eq',
                    'value' => 10
                ]
            ], JSON_THROW_ON_ERROR)
        );
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame("(`numeric` = :numeric) ", $noteListing->getCondition());
        $this->assertSame(
            [
                'numeric' => 10
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
        $noteParameters = new NoteParameters(
            fieldFilters: json_encode([
                [
                    'field' => 'boolean',
                    'type' => 'boolean',
                    'operator' => 'boolean',
                    'value' => true
                ]
            ], JSON_THROW_ON_ERROR)
        );
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame("(`boolean` = :boolean) ", $noteListing->getCondition());
        $this->assertSame(
            [
                'boolean' => 1
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
        $noteParameters = new NoteParameters(
            fieldFilters: json_encode([
                [
                    'field' => 'list',
                    'type' => 'list',
                    'operator' => 'list',
                    'value' => 'list'
                ]
            ], JSON_THROW_ON_ERROR)
        );
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame("(`list` = :list) ", $noteListing->getCondition());
        $this->assertSame(
            [
                'list' => 'list'
            ],
            $noteListing->getConditionVariables()
        );
    }

    /**
     * @throws JsonException
     */
    public function testApplyFieldFiltersUser(): void
    {
        $noteListing = $this->getNoteListing();
        $noteParameters = new NoteParameters(
            fieldFilters: json_encode([
                [
                    'field' => 'user',
                    'type' => 'user',
                    'operator' => 'user',
                    'value' => 'admin'
                ]
            ], JSON_THROW_ON_ERROR)
        );
        $this->filterService->applyFieldFilters($noteListing, $noteParameters);

        $this->assertSame(
            "(`user` IN (SELECT `id` FROM `users` WHERE `name` = :user))  AND (`user` = :user) ",
            $noteListing->getCondition()
        );
        $this->assertSame(
            [
                'user' => 'admin'
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
        $noteParameters = new NoteParameters(
            fieldFilters: 'invalid'
        );

        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('Invalid filter: fieldFilters');

        $this->filterService->applyFieldFilters($noteListing, $noteParameters);
    }

    private function getNoteListing(): NoteListing
    {
        return new NoteListing();
    }


}