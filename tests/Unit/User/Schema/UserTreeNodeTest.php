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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Schema;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;

/**
 * @internal
 */
final class UserTreeNodeTest extends Unit
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
