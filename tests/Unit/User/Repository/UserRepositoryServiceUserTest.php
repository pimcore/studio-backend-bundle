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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Repository;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepository;
use Pimcore\Model\User;

/**
 * Permission, role and MCP server access assignments resolve users by id or name; service
 * users must be found there too, so an administrator can grant them access.
 *
 * @internal
 */
final class UserRepositoryServiceUserTest extends Unit
{
    public function testUsersByIdsIncludeServiceUsers(): void
    {
        $human = $this->user(new User(), 1, 'jane');
        $serviceUser = $this->user(User::newServiceUser(), 7, 'svc-erp-sync');

        $connection = $this->makeEmpty(Connection::class, [
            'fetchFirstColumn' => Expected::once(
                function (string $sql, array $params, array $types): array {
                    $this->assertStringContainsString('id IN (:values)', $sql);
                    $this->assertStringContainsString('`type` IN (:types)', $sql);
                    $this->assertSame([1, 7, 9], $params['values']);
                    $this->assertSame(['user', 'serviceuser'], $params['types']);
                    $this->assertSame(ArrayParameterType::INTEGER, $types['values']);
                    $this->assertSame(ArrayParameterType::STRING, $types['types']);

                    return [1, 7];
                }
            ),
        ]);

        $repository = new UserRepository(
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->resolver([1 => $human, 7 => $serviceUser]),
            $connection,
        );

        $this->assertSame([$human, $serviceUser], $repository->getUsersByIds([1, 7, 9]));
    }

    public function testUsersByNamesIncludeServiceUsers(): void
    {
        $human = $this->user(new User(), 1, 'jane');
        $serviceUser = $this->user(User::newServiceUser(), 7, 'svc-erp-sync');

        $connection = $this->makeEmpty(Connection::class, [
            'fetchFirstColumn' => Expected::once(
                function (string $sql, array $params, array $types): array {
                    $this->assertStringContainsString('name IN (:values)', $sql);
                    $this->assertSame(['jane', 'svc-erp-sync'], $params['values']);
                    $this->assertSame(['user', 'serviceuser'], $params['types']);
                    $this->assertSame(ArrayParameterType::STRING, $types['values']);

                    return ['1', '7'];
                }
            ),
        ]);

        $repository = new UserRepository(
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->resolver([1 => $human, 7 => $serviceUser]),
            $connection,
        );

        $this->assertSame([$human, $serviceUser], $repository->getUsersByNames(['jane', 'svc-erp-sync']));
    }

    public function testRowsThatDoNotLoadAsUsersAreSkipped(): void
    {
        $connection = $this->makeEmpty(Connection::class, ['fetchFirstColumn' => [5]]);

        $repository = new UserRepository(
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->resolver([]),
            $connection,
        );

        $this->assertSame([], $repository->getUsersByIds([5]));
    }

    public function testNoQueryForAnEmptyList(): void
    {
        $repository = new UserRepository(
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->resolver([]),
            $this->makeEmpty(Connection::class, ['fetchFirstColumn' => Expected::never()]),
        );

        $this->assertSame([], $repository->getUsersByIds([]));
        $this->assertSame([], $repository->getUsersByNames([]));
    }

    /**
     * @param array<int, User> $users
     */
    private function resolver(array $users): UserResolverInterface
    {
        return $this->makeEmpty(UserResolverInterface::class, [
            'getById' => static fn (int $id): ?User => $users[$id] ?? null,
        ]);
    }

    private function user(User $user, int $id, string $name): User
    {
        $user->setId($id);
        $user->setName($name);

        return $user;
    }
}
