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

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Permission\PermissionTypes;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\DocumentSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResult;
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
     *
     * @dataProvider elementTypeProvider
     */
    public function testFolderAggregationRunsForTheUserWithViewPermission(string $elementType): void
    {
        $user = new User();
        $captured = [];
        $search = static function (object $search, PermissionTypes $permissionType) use (&$captured, $elementType): object {
            $captured[] = [$search->getUser(), $permissionType];

            return match ($elementType) {
                ElementTypes::TYPE_ASSET => new AssetSearchResult([], new PaginationInfo(0, 1, 1, 1)),
                ElementTypes::TYPE_DOCUMENT => new DocumentSearchResult([], new PaginationInfo(0, 1, 1, 1)),
                default => new DataObjectSearchResult([], new PaginationInfo(0, 1, 1, 1)),
            };
        };

        $service = new TreePqlService(
            $this->makeEmpty(SearchProviderInterface::class, [
                'createAssetSearch' => new AssetSearch(),
                'createDataObjectSearch' => new DataObjectSearch(),
                'createDocumentSearch' => new DocumentSearch(),
            ]),
            $this->makeEmpty(AssetSearchServiceInterface::class, ['search' => $search]),
            $this->makeEmpty(DataObjectSearchServiceInterface::class, ['search' => $search]),
            $this->makeEmpty(DocumentSearchServiceInterface::class, ['search' => $search]),
            $this->makeEmpty(LoggerInterface::class),
        );

        $service->getRelevantChildFolderKeys(1, 'key = "a"', $elementType, $user);

        $this->assertSame([[$user, PermissionTypes::VIEW]], $captured);
    }

    public static function elementTypeProvider(): array
    {
        return [
            'asset' => [ElementTypes::TYPE_ASSET],
            'data object' => [ElementTypes::TYPE_OBJECT],
            'document' => [ElementTypes::TYPE_DOCUMENT],
        ];
    }
}
