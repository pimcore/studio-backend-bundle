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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Perspective\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\Search\LocateInTreeServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\PathServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\TreeQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetElementData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\ElementTreeWidgetRepository;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;

/**
 * Regression coverage for PEES-2058 (Copilot follow-up on #2255): locating an element inside a
 * custom-perspective widget must not require direct VIEW permission on the ancestor folders
 * between the widget's configured root and that element.
 *
 * @internal
 */
final class ElementTreeWidgetRepositoryTest extends Unit
{
    public function testGetTreeLevelDataDoesNotRequireViewPermissionOnAncestorFolders(): void
    {
        $rootPath = '/Products';
        $widget = new ElementTreeWidgetConfig(
            'products_widget',
            'Products',
            [],
            new ElementIcon('name', 'folder'),
            ElementTypes::TYPE_DATA_OBJECT,
            new RelatedElementData(101241, ElementTypes::TYPE_OBJECT, 'folder', $rootPath),
        );

        $element = $this->createMock(ElementSearchResultItemInterface::class);
        $element->method('getFullPath')->willReturn('/Products/Chucking Equipment/obj123');
        $element->method('getId')->willReturn(999);

        $pathService = $this->createMock(PathServiceInterface::class);
        $pathService->method('getAllParentPaths')->willReturn([
            '/',
            '/Products',
            '/Products/Chucking Equipment',
        ]);

        $elementService = $this->createMock(ElementServiceInterface::class);
        $elementService->method('getElementIdByPath')->willReturnCallback(
            fn ($type, $pathParameter) => match ($pathParameter->getPath()) {
                '/Products' => 101241,
                '/Products/Chucking Equipment' => 101242,
            }
        );
        // A direct permission check on either ancestor would throw for this user - asserting
        // it is never called is the point of this test.
        $elementService->method('getAllowedElementByPath')->willThrowException(
            new ForbiddenException('You dont have VIEW permission')
        );

        $search = $this->createMock(SearchInterface::class);
        $query = $this->createMock(QueryInterface::class);
        $query->method('getSearch')->willReturn($search);

        $treeQuery = $this->createMock(TreeQueryInterface::class);
        $treeQuery->method('get')->willReturn($query);

        $locateInTreeService = $this->createMock(LocateInTreeServiceInterface::class);
        $locateInTreeService->method('getPageNumber')->willReturn(1);

        $repository = new ElementTreeWidgetRepository(
            $elementService,
            $this->createMock(ElementSearchServiceInterface::class),
            $locateInTreeService,
            $pathService,
            $treeQuery,
        );

        $result = $repository->getTreeLevelData(
            new WidgetElementData($widget, $element),
            $this->createMock(SearchIndexFilterInterface::class),
            $this->createMock(UserInterface::class),
        );

        $this->assertCount(2, $result);
        $this->assertSame(101241, $result[0]->getParentId());
        $this->assertSame(101242, $result[0]->getElementId());
        $this->assertSame(101242, $result[1]->getParentId());
        $this->assertSame(999, $result[1]->getElementId());
    }
}
