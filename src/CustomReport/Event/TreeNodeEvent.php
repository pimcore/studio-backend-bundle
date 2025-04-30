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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Event;

use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class TreeNodeEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.custom_report_tree_node';

    public function __construct(
        private readonly CustomReportTreeNode $treeNode
    ) {
        parent::__construct($this->treeNode);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getTreeNode(): CustomReportTreeNode
    {
        return $this->treeNode;
    }
}
