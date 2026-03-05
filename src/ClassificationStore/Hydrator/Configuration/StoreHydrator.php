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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreTreeNode;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * @internal
 */
final readonly class StoreHydrator implements StoreHydratorInterface
{
    public function hydrateStoreDetail(StoreConfig $storeConfig): StoreDetail
    {
        return new StoreDetail(
            $storeConfig->getId(),
            $storeConfig->getName(),
            $storeConfig->getDescription(),
        );
    }

    public function hydrateStoreTreeNode(StoreConfig $storeConfig): StoreTreeNode
    {
        return new StoreTreeNode(
            $storeConfig->getId(),
            $storeConfig->getName(),
            $storeConfig->getDescription(),
        );
    }
}
