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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Event;

use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeConfigNode;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class TreeConfigNodeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.custom_report_tree_config_node';

    public function __construct(
        private readonly CustomReportTreeConfigNode|CustomReportTreeNodeFolder $treeConfigNode
    ) {
        parent::__construct($this->treeConfigNode);
    }

    public function getTreeConfigNode(): CustomReportTreeConfigNode|CustomReportTreeNodeFolder
    {
        return $this->treeConfigNode;
    }
}
