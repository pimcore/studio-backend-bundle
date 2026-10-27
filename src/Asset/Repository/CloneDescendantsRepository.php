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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Repository;

use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model\CloneDescendants;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Listing as AssetListing;

/**
 * The descendants are read from the database, not from the search index: a clone must contain everything that exists,
 * also elements whose index update is still pending.
 *
 * @internal
 */
final readonly class CloneDescendantsRepository implements CloneDescendantsRepositoryInterface
{
    public function getDescendants(Asset $asset): CloneDescendants
    {
        $folders = $this->createListing($asset, '`type` = ?');
        // A shorter path is never below a longer one, so every folder is listed after its parent
        $folders->setOrderKey(['LENGTH(`path`)', '`path`', '`filename`'], false);
        $folders->setOrder('ASC');

        return new CloneDescendants(
            $folders->loadIdList(),
            $this->createListing($asset, '`type` != ?')->loadIdList()
        );
    }

    private function createListing(Asset $asset, string $typeCondition): AssetListing
    {
        $listing = new AssetListing();
        $listing->setCondition(
            '`path` LIKE ? AND ' . $typeCondition,
            [$listing->escapeLike($asset->getRealFullPath()) . '/%', ElementTypes::TYPE_FOLDER]
        );

        return $listing;
    }
}
