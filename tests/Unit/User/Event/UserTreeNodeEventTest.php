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
