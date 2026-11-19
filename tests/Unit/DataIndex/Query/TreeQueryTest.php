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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\TreeQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Folder;
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

        $user = $this->createMock(UserInterface::class);
        // The query must carry the viewing user before filters run - otherwise the GDI
        // workspace permission restrictions never apply to the search (PEES-2058 follow-up).
        $query->expects($this->once())->method('setUser')->with($user)->willReturnSelf();

        $result = $this->createTreeQuery($query)->get(
            $this->createAssetWidget(),
            $this->createFilterService(),
            $user,
            1
        );

        $this->assertSame($query, $result);
        $this->assertSame(['orderFoldersFirst', 'orderByPath:asc'], $calls);
    }

    public function testDataObjectTreeSortingDoesNotRequireViewPermissionOnTheWidgetRoot(): void
    {
        $rootPath = '/Products/Chucking Equipment';
        $query = $this->createMock(DataObjectQueryInterface::class);
        $query->method('filterParentId')->willReturnSelf();
        $query->method('filterPath')->willReturnSelf();
        $query->method('setPageSize')->willReturnSelf();

        $queryFactory = $this->createMock(QueryFactoryInterface::class);
        $queryFactory->method('create')->with(ElementTypes::TYPE_DATA_OBJECT)->willReturn($query);

        $settingsService = $this->createMock(SettingsServiceInterface::class);
        $settingsService->method('getTreePageSize')->willReturn(30);

        $folder = $this->createMock(Folder::class);

        $elementService = $this->createMock(ElementServiceInterface::class);
        $elementService->method('getElementIdByPath')->willReturn(101242);
        $elementService->method('getElementById')->willReturn($folder);
        // A direct permission check on the widget's configured root would throw for this
        // user - asserting it is never called is the point of this test (PEES-2058).
        $elementService->method('getAllowedElementByPath')->willThrowException(
            new ForbiddenException('You dont have VIEW permission')
        );

        $dataObjectService = $this->createMock(DataObjectServiceInterface::class);
        $dataObjectService->expects($this->once())->method('setTreeSorting')->with($folder, $query, false);

        $user = $this->createMock(UserInterface::class);
        $query->expects($this->once())->method('setUser')->with($user)->willReturnSelf();

        $treeQuery = new TreeQuery($queryFactory, $settingsService, $dataObjectService, $elementService);

        $result = $treeQuery->get(
            $this->createDataObjectWidget($rootPath),
            $this->createFilterService(),
            $user,
            101242
        );

        $this->assertSame($query, $result);
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

    private function createDataObjectWidget(string $rootPath): ElementTreeWidgetConfig
    {
        return new ElementTreeWidgetConfig(
            'products_widget',
            'Products',
            [],
            new ElementIcon('name', 'folder'),
            ElementTypes::TYPE_DATA_OBJECT,
            new RelatedElementData(101241, ElementTypes::TYPE_OBJECT, 'folder', $rootPath),
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
