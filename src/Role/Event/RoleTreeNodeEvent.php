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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;

final class RoleTreeNodeEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.role_tree_node';

    public function __construct(private readonly TreeNode $roleNode)
    {
        parent::__construct($roleNode);
    }

    public function getRoleTreeNode(): TreeNode
    {
        return $this->roleNode;
    }
}
