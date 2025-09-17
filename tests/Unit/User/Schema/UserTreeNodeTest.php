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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;

/**
 * @internal
 */
#[CoversClass(TreeNode::class)]
final class UserTreeNodeTest extends TestCase
{
    public function testGetId(): void
    {
        $id = 1;
        $userTreeNode = new TreeNode($id, 'name', 'user', true);

        $this->assertSame($id, $userTreeNode->getId());
    }

    public function testGetName(): void
    {
        $name = 'name';
        $userTreeNode = new TreeNode(1, $name, 'user', true);

        $this->assertSame($name, $userTreeNode->getName());
    }

    public function testGetType(): void
    {
        $type = 'user';
        $userTreeNode = new TreeNode(1, 'name', $type, true);

        $this->assertSame($type, $userTreeNode->getType());
    }

    public function testIsHasChildren(): void
    {
        $hasChildren = false;
        $userTreeNode = new TreeNode(1, 'name', 'user', $hasChildren);

        $this->assertSame($hasChildren, $userTreeNode->hasChildren());
    }
}
