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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Hydrator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydrator;
use Pimcore\Model\User;

/**
 * @internal
 */
#[CoversClass(UserTreeNodeHydrator::class)]
#[UsesClass(TreeNode::class)]
final class UserTreeNodeHydratorTest extends TestCase
{
    public function testHydrateWithUser(): void
    {
        $user = new User();
        $user->setId(1);
        $user->setName('User XYZ');
        $user->setType('user Type');

        $hydrator = new UserTreeNodeHydrator();
        $userTreeNode = $hydrator->hydrate($user);

        $this->assertSame(1, $userTreeNode->getId());
        $this->assertSame('User XYZ', $userTreeNode->getName());
        $this->assertSame('user Type', $userTreeNode->getType());
        $this->assertFalse($userTreeNode->hasChildren());
    }

    public function testHydrateWithFolder(): void
    {
        $folder = new User\Folder();
        $folder->setId(1);
        $folder->setName('Folder XYZ');
        $folder->setType('folder Type');
        $folder->setChildren([0 => new User()]);

        $hydrator = new UserTreeNodeHydrator();
        $userTreeNode = $hydrator->hydrate($folder);

        $this->assertSame(1, $userTreeNode->getId());
        $this->assertSame('Folder XYZ', $userTreeNode->getName());
        $this->assertSame('folder Type', $userTreeNode->getType());
        $this->assertTrue($userTreeNode->hasChildren());
    }
}
