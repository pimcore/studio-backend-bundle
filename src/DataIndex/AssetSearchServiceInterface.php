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

use Pimcore\Bundle\GenericDataIndexBundle\Exception\AssetSearchException;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;

interface AssetSearchServiceInterface
{
    /**
     * @throws SearchException|InvalidArgumentException
     */
    public function searchAssets(AssetQueryInterface $assetQuery): AssetSearchResult;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetById(
        int $id,
        ?UserInterface $user = null
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video;

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchAssetIds(AssetQueryInterface $assetQuery): array;

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function getChildrenIds(
        string $parentPath,
        ?string $sortDirection = null
    ): array;

    /**
     * @throws SearchException
     *
     */
    public function countChildren(
        string $parentPath,
        ?string $sortDirection = null
    ): int;

    /**
     * @throws AssetSearchException
     */
    public function getTotalFileSizeByIds(array $ids): int;
}
