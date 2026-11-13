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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepository;
use Pimcore\Model\User;

/**
 * The repository is the gate of every service-user endpoint that takes an id: a person's id must not pass,
 * since the type no longer proves the kind of user.
 *
 * @internal
 */
final class ServiceUserRepositoryTest extends Unit
{
    public function testGetServiceUserReturnsTheServiceUserOfTheId(): void
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setId(7);

        $this->assertSame($serviceUser, $this->repository($serviceUser)->getServiceUser(7));
    }

    public function testGetServiceUserRefusesAPersonAsNotFound(): void
    {
        $person = new User();
        $person->setId(3);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service user with ID: 3 not found');
        $this->repository($person)->getServiceUser(3);
    }

    public function testGetServiceUserRefusesAnUnknownIdAsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->repository(null)->getServiceUser(99);
    }

    private function repository(?User $resolved): ServiceUserRepository
    {
        return new ServiceUserRepository(
            $this->makeEmpty(UserResolverInterface::class, ['getById' => $resolved]),
        );
    }
}
