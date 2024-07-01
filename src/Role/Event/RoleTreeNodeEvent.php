<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
