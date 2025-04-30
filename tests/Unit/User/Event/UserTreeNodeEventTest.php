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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Event;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserTreeNodeEvent;

/**
 * @internal
 */
final class UserTreeNodeEventTest extends Unit
{
    public function testGetUserTreeNode(): void
    {
        $userTreeNode = new TreeNode(
            id: 1,
            name: 'name',
            type: 'type',
            hasChildren: true,
        );
        $event = new UserTreeNodeEvent($userTreeNode);
        $this->assertSame($userTreeNode, $event->getUserTreeNode());
    }
}
