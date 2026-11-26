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

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\User\Role\RoleResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\ContentLanguagesHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\TwoFactorAuthHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserInformationHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorAuth;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Model\User\Role;
use Pimcore\Model\UserInterface;

/**
 * Regression coverage for pimcore/platform-version#851: the current-user information must
 * report the classes and document types granted through the user's roles, the same way
 * Pimcore Core's User::isAllowed() merges them.
 *
 * @internal
 */
final class UserInformationHydratorTest extends Unit
{
    public function testClassesAndDocTypesIncludeRoleGrants(): void
    {
        $role = new Role();
        $role->setClasses(['ExampleProduct', 'ExampleBrand']);
        $role->setDocTypes(['role-doc-type']);

        $user = $this->createUser(
            classes: ['ExampleBrand'],
            docTypes: ['user-doc-type'],
            roles: [5]
        );

        $information = $this->createHydrator([5 => $role])->hydrate($user);

        $this->assertSame(['ExampleBrand', 'ExampleProduct'], $information->getClasses());
        $this->assertSame(['user-doc-type', 'role-doc-type'], $information->getDocTypes());
    }

    public function testRoleGrantsApplyWhenUserHasNoDirectRestriction(): void
    {
        $role = new Role();
        $role->setClasses(['ExampleProduct']);

        $user = $this->createUser(classes: [], docTypes: [], roles: [5]);

        $information = $this->createHydrator([5 => $role])->hydrate($user);

        $this->assertSame(['ExampleProduct'], $information->getClasses());
        $this->assertSame([], $information->getDocTypes());
    }

    public function testMissingRoleIsSkipped(): void
    {
        $user = $this->createUser(classes: ['ExampleBrand'], docTypes: [], roles: [99]);

        $information = $this->createHydrator([])->hydrate($user);

        $this->assertSame(['ExampleBrand'], $information->getClasses());
    }

    private function createUser(array $classes, array $docTypes, array $roles): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'getId' => 7,
            'getUsername' => 'editor',
            'isAdmin' => false,
            'getClasses' => $classes,
            'getDocTypes' => $docTypes,
            'getRoles' => $roles,
            'getLanguage' => 'en',
            'getWelcomeScreen' => false,
            'getMemorizeTabs' => false,
            'getAllowDirtyClose' => false,
            'hasImage' => false,
        ]);
    }

    /**
     * @param array<int, Role> $roles
     */
    private function createHydrator(array $roles): UserInformationHydrator
    {
        $roleResolver = $this->makeEmpty(RoleResolverInterface::class, [
            'getById' => static fn (int $id): ?Role => $roles[$id] ?? null,
        ]);

        return new UserInformationHydrator(
            $this->makeEmpty(ContentLanguagesHydratorInterface::class, ['hydrate' => []]),
            $this->makeEmpty(KeyBindingServiceInterface::class, ['hydrateKeyBindings' => []]),
            $roleResolver,
            $this->makeEmpty(TwoFactorAuthHydratorInterface::class, [
                'hydrate' => new TwoFactorAuth(false, false, '', false),
            ]),
            $this->makeEmpty(UserPermissionServiceInterface::class, ['getAllowedUserPermissions' => []]),
            $this->makeEmpty(UserPerspectiveServiceInterface::class, [
                'getActivePerspective' => 'studio_default_perspective',
                'getAllowedPerspectives' => [],
            ]),
        );
    }
}
