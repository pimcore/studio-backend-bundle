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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Service\Filter;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Permission\PermissionTypes;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Paging\PaginationInfo;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Document\DocumentSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Filter\TreePqlService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class TreePqlServiceTest extends Unit
{
    /**
     * Folder keys must only come from elements the user may view, otherwise the tree reveals where elements
     * the user may not view match the query.
     */
    public function testFolderAggregationRunsForTheUserWithViewPermission(): void
    {
        $user = new User();
        $captured = null;

        $service = new TreePqlService(
            $this->makeEmpty(SearchProviderInterface::class, ['createAssetSearch' => new AssetSearch()]),
            $this->makeEmpty(AssetSearchServiceInterface::class, [
                'search' => Expected::once(
                    function (AssetSearch $search, PermissionTypes $permissionType) use (&$captured): AssetSearchResult {
                        $captured = [$search->getUser(), $permissionType];

                        return new AssetSearchResult([], new PaginationInfo(0, 1, 1, 1));
                    }
                ),
            ]),
            $this->makeEmpty(DataObjectSearchServiceInterface::class),
            $this->makeEmpty(DocumentSearchServiceInterface::class),
            $this->makeEmpty(LoggerInterface::class),
        );

        $service->getRelevantChildFolderKeys(1, 'filename = "a"', ElementTypes::TYPE_ASSET, $user);

        $this->assertSame([$user, PermissionTypes::VIEW], $captured);
    }
}
