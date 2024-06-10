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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydrator;
use Pimcore\Model\User;

/**
 * @internal
 */
final class UserTreeNodeHydratorTest extends Unit
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