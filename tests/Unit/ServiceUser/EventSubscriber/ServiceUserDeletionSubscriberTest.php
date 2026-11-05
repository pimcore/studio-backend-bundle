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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\EventSubscriber;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\EventSubscriber\ServiceUserDeletionSubscriber;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\AccessRevocationServiceInterface;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
final class ServiceUserDeletionSubscriberTest extends Unit
{
    public function testListensBeforeAUserIsDeleted(): void
    {
        $this->assertArrayHasKey(UserRoleEvents::PRE_DELETE, ServiceUserDeletionSubscriber::getSubscribedEvents());
    }

    public function testRevokesEverythingWhenAServiceUserIsDeleted(): void
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId(7);

        $revocation = $this->makeEmpty(AccessRevocationServiceInterface::class, [
            'revokeAll' => Expected::once(function (ServiceUser $revoked) use ($serviceUser): void {
                $this->assertSame($serviceUser, $revoked);
            }),
        ]);

        (new ServiceUserDeletionSubscriber($revocation))->onPreDelete(new UserRoleEvent($serviceUser));
    }

    public function testLeavesHumanUsersAlone(): void
    {
        $user = new User();
        $user->setId(3);

        $revocation = $this->makeEmpty(AccessRevocationServiceInterface::class, [
            'revokeAll' => Expected::never(),
        ]);

        (new ServiceUserDeletionSubscriber($revocation))->onPreDelete(new UserRoleEvent($user));
    }
}
