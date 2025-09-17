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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserTreeNodeEvent;

/**
 * @internal
 */
#[CoversClass(UserTreeNodeEvent::class)]
#[UsesClass(AbstractPreResponseEvent::class)]
#[UsesClass(TreeNode::class)]
final class UserTreeNodeEventTest extends TestCase
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
