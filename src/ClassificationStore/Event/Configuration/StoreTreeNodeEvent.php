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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class StoreTreeNodeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.classification_store.configuration.store_tree_node';

    public function __construct(
        private readonly StoreTreeNode $storeTreeNode
    ) {
        parent::__construct($storeTreeNode);
    }

    public function getStoreTreeNode(): StoreTreeNode
    {
        return $this->storeTreeNode;
    }
}
