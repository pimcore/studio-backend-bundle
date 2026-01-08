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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\SelectOption;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTree;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionTreeFolder;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class TreeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.select_option.tree';

    public function __construct(private readonly SelectOptionTree|SelectOptionTreeFolder $treeNode)
    {
        parent::__construct($this->treeNode);
    }

    public function getSelectOptionTreeNode(): SelectOptionTree|SelectOptionTreeFolder
    {
        return $this->treeNode;
    }
}
