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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Grid;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearch;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class GridSearchTest extends Unit
{
    private const ROOT_FOLDER_ID = 1;

    /**
     * The root folder may be invisible to the search index lookup (restricted user
     * workspaces exclude "/", or the index misses the root document). A grid search
     * anchored on the root folder must not depend on that lookup: its path is "/" by
     * definition. Regression test for the data-quality drill-down 404
     * ("DataObject with ID: 1 not found").
     */
    public function testRootFolderSearchSkipsIndexLookupAndFiltersByRootPath(): void
    {
        $capturedFilter = null;

        $gridSearch = $this->createGridSearch(
            $this->makeEmpty(DataObjectSearchServiceInterface::class, [
                'getDataObjectById' => Expected::never(),
                'searchDataObjects' => new DataObjectSearchResult([], 1, 50, 0),
            ]),
            $capturedFilter
        );

        $gridSearch->searchDataObjects(new GridParameter(self::ROOT_FOLDER_ID, [], null));

        $this->assertInstanceOf(FilterParameter::class, $capturedFilter);
        $this->assertSame('/', $capturedFilter->getPath());
    }

    public function testNonRootFolderSearchResolvesFolderPathFromIndex(): void
    {
        $folderId = 42;
        $capturedFilter = null;

        $gridSearch = $this->createGridSearch(
            $this->makeEmpty(DataObjectSearchServiceInterface::class, [
                'getDataObjectById' => Expected::once($this->createFolder($folderId, '/Cars')),
                'searchDataObjects' => new DataObjectSearchResult([], 1, 50, 0),
            ]),
            $capturedFilter
        );

        $gridSearch->searchDataObjects(new GridParameter($folderId, [], null));

        $this->assertInstanceOf(FilterParameter::class, $capturedFilter);
        $this->assertSame('/Cars', $capturedFilter->getPath());
    }

    private function createGridSearch(
        DataObjectSearchServiceInterface $dataObjectSearchService,
        mixed &$capturedFilter
    ): GridSearch {
        $filterService = $this->makeEmpty(SearchIndexFilterInterface::class, [
            'applyFilters' => function ($query, $parameters, $type) use (&$capturedFilter) {
                $capturedFilter = $parameters;

                return $query;
            },
        ]);

        return new GridSearch(
            $this->makeEmpty(AssetSearchServiceInterface::class),
            $dataObjectSearchService,
            $this->makeEmpty(DocumentSearchServiceInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class, ['create' => $filterService]),
            $this->makeEmpty(QueryFactoryInterface::class, [
                'create' => $this->makeEmpty(DataObjectQueryInterface::class),
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ])
        );
    }

    private function createFolder(int $id, string $fullPath): DataObjectFolder
    {
        return new DataObjectFolder(
            key: ltrim($fullPath, '/'),
            className: 'folder',
            type: 'folder',
            published: true,
            hasChildren: true,
            hasWorkflowWithPermissions: false,
            fullPath: $fullPath,
            permissions: new DataObjectPermissions(),
            index: 0,
            childrenSortBy: 'key',
            childrenSortOrder: 'asc',
            allowVariants: null,
            id: $id,
            parentId: 1,
            path: '/',
            icon: new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            userOwner: 1,
            userModification: null,
            locked: null,
            isLocked: false,
            creationDate: null,
            modificationDate: null
        );
    }
}
