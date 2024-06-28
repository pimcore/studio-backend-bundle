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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use function count;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Folder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter\AssetSearchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;

final readonly class AssetSearchService implements AssetSearchServiceInterface
{
    public function __construct(
        private AssetSearchAdapterInterface $assetSearchAdapter,
        private AssetQueryProviderInterface $assetQueryProvider
    ) {
    }

    /**
     * @throws SearchException
     */
    public function searchAssets(QueryInterface $assetQuery): AssetSearchResult
    {
        return $this->assetSearchAdapter->searchAssets($assetQuery);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetById(int $id): Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
    {
        return $this->assetSearchAdapter->getAssetById($id);
    }

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchAssetIds(QueryInterface $assetQuery): array
    {
        return $this->assetSearchAdapter->fetchAssetIds($assetQuery);
    }

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function getChildrenIds(
        string $parentPath,
        ?string $sortDirection = null
    ): array {
        $query = $this->assetQueryProvider->createAssetQuery();
        $query->filterPath($parentPath, true, false);
        if ($sortDirection) {
            $query->orderByPath($sortDirection);
        }

        return $this->fetchAssetIds($query);
    }

    public function countChildren(
        string $parentPath,
        ?string $sortDirection = null
    ): int {

        return count($this->getChildrenIds($parentPath, $sortDirection));
    }
}
