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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter;

use Pimcore\Bundle\GenericDataIndexBundle\Exception\AssetSearchException;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;

interface AssetSearchAdapterInterface
{
    /**
     * @throws SearchException|InvalidArgumentException
     */
    public function searchAssets(AssetQueryInterface $assetQuery): AssetSearchResult;

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetById(int $id, ?UserInterface $user = null): Asset;

    /**
     * @throws SearchException
     *
     * @return array<int>
     */
    public function fetchAssetIds(QueryInterface $assetQuery): array;

    /**
     * @throws AssetSearchException
     */
    public function getTotalFileSizeByIds(QueryInterface $assetQuery): int;
}
