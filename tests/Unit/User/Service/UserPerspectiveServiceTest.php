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
 * Regression coverage for PEES-1390 / GitHub pimcore/platform-version#253:
 * a Superadmin who still has a stale, non-empty perspective assignment stored on their
 * user record must not be restricted by it, since Superadmins bypass all permission checks.
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

    public function testSuperadminGetsDefaultActivePerspectiveDespiteStaleAssignment(): void
    {
        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
            'isAdmin' => true,
        ]);

        // Uses a real, still-configured perspective ID (one of the two in the mocked full set),
        // not a fake one - otherwise this test would pass even without the isAdmin() bypass in
        // getActivePerspective(), since a nonexistent stale ID can never match the full set either
        // way and the bug would go unnoticed.
        $service = $this->createService(
            listUserPerspectives: ['other-perspective'],
            getUserActivePerspective: 'other-perspective',
        );

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

    private function createService(
        array $listUserPerspectives = [],
        ?string $getUserActivePerspective = null,
    ): UserPerspectiveService {
        $repository = $this->makeEmpty(PerspectiveRepositoryInterface::class, [
            'listUserPerspectives' => $listUserPerspectives,
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
