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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class TreeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.objectBrick.tree';

    public function __construct(private readonly ObjectBrickTreeNode|ObjectBrickTreeNodeFolder $treeNode)
    {
        parent::__construct($this->treeNode);
    }

    public function getObjectBrickTreeNode(): ObjectBrickTreeNode|ObjectBrickTreeNodeFolder
    {
        return $this->treeNode;
    }
}
