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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Query;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\TreeQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class TreeQueryTest extends Unit
{
    public function testAssetTreeIsSortedByFoldersFirstAndThenByPath(): void
    {
        $calls = [];
        $query = $this->createMock(AssetQueryInterface::class);
        $query->method('orderFoldersFirst')->willReturnCallback(function () use (&$calls, $query) {
            $calls[] = 'orderFoldersFirst';

            return $query;
        });
        $query->method('orderByPath')->willReturnCallback(function (string $direction) use (&$calls, $query) {
            $calls[] = 'orderByPath:' . $direction;

            return $query;
        });

        $result = $this->createTreeQuery($query)->get(
            $this->createAssetWidget(),
            $this->createFilterService(),
            $this->createMock(UserInterface::class),
            1
        );

        $this->assertSame($query, $result);
        $this->assertSame(['orderFoldersFirst', 'orderByPath:asc'], $calls);
    }

    private function createTreeQuery(AssetQueryInterface $query): TreeQuery
    {
        $queryFactory = $this->createMock(QueryFactoryInterface::class);
        $queryFactory->method('create')->with(ElementTypes::TYPE_ASSET)->willReturn($query);

        $settingsService = $this->createMock(SettingsServiceInterface::class);
        $settingsService->method('getTreePageSize')->willReturn(30);

        return new TreeQuery(
            $queryFactory,
            $settingsService,
            $this->createMock(DataObjectServiceInterface::class),
            $this->createMock(ElementServiceInterface::class),
        );
    }

    private function createFilterService(): SearchIndexFilterInterface
    {
        $filterService = $this->createMock(SearchIndexFilterInterface::class);
        $filterService->method('applyFilters')->willReturnArgument(0);

        return $filterService;
    }

    private function createAssetWidget(): ElementTreeWidgetConfig
    {
        return new ElementTreeWidgetConfig(
            'asset_tree',
            'Asset Tree',
            [],
            new ElementIcon('name', 'asset'),
            ElementTypes::TYPE_ASSET,
        );
    }
}
