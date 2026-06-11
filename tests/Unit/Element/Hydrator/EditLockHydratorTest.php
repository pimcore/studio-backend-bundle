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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\EditLockHydrator;
use Pimcore\Model\Element\Editlock;
use Pimcore\Model\User;

/**
 * @internal
 */
final class EditLockHydratorTest extends Unit
{
    public function testNullModelReturnsUnlockedEditLock(): void
    {
        $hydrator = $this->createHydrator();

        $result = $hydrator->hydrateEditLock(null);

        $this->assertFalse($result->isLocked());
        $this->assertNull($result->getUserId());
        $this->assertNull($result->getDate());
        $this->assertNull($result->getUser());
    }

    public function testLockedModelWithKnownUserReturnsFullEditLock(): void
    {
        $user = new User();
        $user->setName('admin');

        $hydrator = $this->createHydrator(
            $this->makeEmpty(UserResolverInterface::class, [
                'getById' => $user,
            ])
        );

        $model = $this->createEditlockModel(42, 1700000000);

        $result = $hydrator->hydrateEditLock($model);

        $this->assertTrue($result->isLocked());
        $this->assertSame(42, $result->getUserId());
        $this->assertSame(1700000000, $result->getDate());
        $this->assertNotNull($result->getUser());
        $this->assertSame('admin', $result->getUser()->getName());
    }

    public function testLockedModelWithUnknownUserReturnsEditLockWithoutUser(): void
    {
        $hydrator = $this->createHydrator(
            $this->makeEmpty(UserResolverInterface::class, [
                'getById' => null,
            ])
        );

        $model = $this->createEditlockModel(99, 1700000000);

        $result = $hydrator->hydrateEditLock($model);

        $this->assertTrue($result->isLocked());
        $this->assertSame(99, $result->getUserId());
        $this->assertSame(1700000000, $result->getDate());
        $this->assertNull($result->getUser());
    }

    private function createHydrator(
        ?UserResolverInterface $userResolver = null,
    ): EditLockHydrator {
        return new EditLockHydrator(
            $userResolver ?? $this->makeEmpty(UserResolverInterface::class),
        );
    }

    private function createEditlockModel(int $userId, int $date): Editlock
    {
        $model = new Editlock();
        $model->setUserId($userId);
        $model->setDate($date);

        return $model;
    }
}
