<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Repository;

use Pimcore\Model\Asset\Listing;

/**
 * @internal
 */
final class AssetsRepository
{

    public function getAssetChildrenByPath(
        string $path,
    ): array {
        $listing = new Listing();
        $listing->setCondition('`path` LIKE ?', [$listing->escapeLike($path) . '/%']);
        $listing->setOrderKey('LENGTH(`path`)', false);
        $listing->setOrder('ASC');

        return $listing->loadIdList();
    }
}