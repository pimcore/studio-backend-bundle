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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionTreeNodeFolder;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ClassDefinitionTreeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.class_definition.tree';

    public function __construct(private readonly ClassDefinitionTreeNode|ClassDefinitionTreeNodeFolder $treeNode)
    {
        parent::__construct($this->treeNode);
    }

    public function getClassDefinitionTreeNode(): ClassDefinitionTreeNode|ClassDefinitionTreeNodeFolder
    {
        return $this->treeNode;
    }
}
