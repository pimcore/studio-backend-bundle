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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Adapter;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidSearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
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
     * @throws InvalidSearchException
     */
    public function getTotalFileSizeByIds(QueryInterface $assetQuery): int;

    /**
     * @throws InvalidSearchException|SearchException
     */
    public function findInTree(QueryInterface $dataObjectQuery): ?ElementSearchResultItemInterface;
}
