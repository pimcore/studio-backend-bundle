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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\PerspectiveConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PerspectiveRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveService;
use Pimcore\Model\UserInterface;

/**
 * Regression coverage for the admin bypass of perspective restrictions: an admin bypasses every
 * other permission check, so a perspective assignment that is still stored on the user record or
 * inherited from one of the user's roles must not restrict them either.
 *
 * @internal
 */
final class UserPerspectiveServiceTest extends Unit
{
    public function testSuperadminGetsFullAllowedPerspectivesDespiteStaleAssignment(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
        ]);

        // Stale data: the repository still reports one restrictive perspective for this user,
        // as if it had been assigned before the promotion to Superadmin.
        $service = $this->createService(listUserPerspectives: ['stale-perspective']);

        $result = $service->getAllowedPerspectives($user);

        $this->assertCount(2, $result);
    }

    public function testNonAdminGetsOnlyAssignedAllowedPerspectives(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => false,
            'getRoles' => [],
        ]);

        $service = $this->createService(listUserPerspectives: ['my-perspective']);

        $result = $service->getAllowedPerspectives($user);

        $this->assertCount(1, $result);
    }

    public function testAdminKeepsExplicitlySelectedActivePerspectiveOutsideStaleAssignment(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
        ]);

        // 'other-perspective' is outside the stale assignment but inside the full set, so it is a
        // perspective the admin is allowed to select - and their selection must survive the next
        // application load instead of being reset.
        $service = $this->createService(
            listUserPerspectives: ['stale-perspective'],
            getUserActivePerspective: 'other-perspective',
        );

        $result = $service->getActivePerspective($user);

        $this->assertSame('other-perspective', $result);
    }

    public function testAdminWithoutSelectedActivePerspectiveGetsDefaultDespiteStaleAssignment(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
        ]);

        $service = $this->createService(listUserPerspectives: ['stale-perspective']);

        $result = $service->getActivePerspective($user);

        $this->assertSame(Perspectives::DEFAULT_ID->value, $result);
    }

    public function testValidatePerspectiveAccessAllowsSuperadminForAnyPerspective(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
        ]);

        $service = $this->createService(listUserPerspectives: ['stale-perspective']);

        // 'other-perspective' is one of the two IDs createService() wires up as the full,
        // unrestricted set - i.e. a perspective outside the stale assignment.
        $service->validatePerspectiveAccess($user, 'other-perspective');
        $this->addToAssertionCount(1);
    }

    public function testValidatePerspectiveAccessStillThrowsForNonAdminOutsideAssignedList(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => false,
            'getRoles' => [],
        ]);

        $service = $this->createService(listUserPerspectives: ['my-perspective']);

        $this->expectException(ForbiddenException::class);
        $service->validatePerspectiveAccess($user, 'some-other-perspective');
    }

    public function testAdminGetsFullAllowedPerspectivesWhenRestrictionComesFromAssignedRole(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
            'getRoles' => [7],
        ]);

        // Nothing is stored on the user record itself - the restriction is inherited from the
        // assigned role, which is what happens when a user is promoted to admin only afterwards.
        $service = $this->createService(perspectivesByRoleId: [7 => ['role-perspective']]);

        $result = $service->getAllowedPerspectives($user);

        $this->assertSame(
            [Perspectives::DEFAULT_ID->value, 'other-perspective'],
            array_map(static fn (PerspectiveConfig $perspective) => $perspective->getId(), $result)
        );
    }

    public function testNonAdminStaysRestrictedByPerspectiveInheritedFromAssignedRole(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => false,
            'getRoles' => [7],
        ]);

        $service = $this->createService(perspectivesByRoleId: [7 => ['role-perspective']]);

        $result = $service->getAllowedPerspectives($user);

        $this->assertCount(1, $result);
    }

    public function testAdminGetsDefaultActivePerspectiveWhenRestrictionComesFromAssignedRole(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
            'getRoles' => [7],
        ]);

        $service = $this->createService(perspectivesByRoleId: [7 => ['role-perspective']]);

        $result = $service->getActivePerspective($user);

        $this->assertSame(Perspectives::DEFAULT_ID->value, $result);
    }

    public function testValidatePerspectiveAccessAllowsAdminBeyondRoleInheritedPerspective(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
            'getRoles' => [7],
        ]);

        $service = $this->createService(perspectivesByRoleId: [7 => ['role-perspective']]);

        $service->validatePerspectiveAccess($user, 'other-perspective');
        $this->addToAssertionCount(1);
    }

    /**
     * @param array<int, string[]> $perspectivesByRoleId
     */
    private function createService(
        array $listUserPerspectives = [],
        ?string $getUserActivePerspective = null,
        array $perspectivesByRoleId = [],
    ): UserPerspectiveService {
        $repository = $this->makeEmpty(PerspectiveRepositoryInterface::class, [
            'listUserPerspectives' => static fn (int $userOrRoleId): array => $perspectivesByRoleId[$userOrRoleId]
                ?? $listUserPerspectives,
            'getUserActivePerspective' => $getUserActivePerspective,
        ]);

        $configRepository = $this->makeEmpty(PerspectiveConfigRepositoryInterface::class, [
            'getConfiguration' => [],
        ]);

        $defaultPerspective = $this->makeEmpty(PerspectiveConfig::class, [
            'getId' => Perspectives::DEFAULT_ID->value,
        ]);
        $otherPerspective = $this->makeEmpty(PerspectiveConfig::class, [
            'getId' => 'other-perspective',
        ]);

        $perspectiveService = $this->makeEmpty(PerspectiveServiceInterface::class, [
            'hydrateListEntry' => $defaultPerspective,
            'listConfigurations' => [$otherPerspective],
        ]);

        return new UserPerspectiveService($repository, $configRepository, $perspectiveService);
    }
}
