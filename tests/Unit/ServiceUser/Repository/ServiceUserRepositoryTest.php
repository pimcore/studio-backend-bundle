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
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepository;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
final class ServiceUserRepositoryTest extends Unit
{
    public function testGetServiceUserReturnsTheServiceUserOfTheId(): void
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId(7);

        $repository = new ServiceUserRepository(
            $this->makeEmpty(UserAccountResolverInterface::class, [
                'getServiceUserById' => static fn (int $id): ?ServiceUser => $id === 7 ? $serviceUser : null,
            ]),
            $this->makeEmpty(Connection::class),
        );

        $this->assertSame($serviceUser, $repository->getServiceUser(7));
    }

    public function testGetServiceUserThrowsNotFoundWhenTheIdIsNoServiceUser(): void
    {
        $repository = new ServiceUserRepository(
            $this->makeEmpty(UserAccountResolverInterface::class, ['getServiceUserById' => null]),
            $this->makeEmpty(Connection::class),
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service user with ID: 3 not found');
        $repository->getServiceUser(3);
    }
}
