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
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\ContentLanguagesHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\TwoFactorAuthHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserInformationHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorAuth;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserSettingsServiceInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserInformationHydratorTest extends Unit
{
    public function testHydrateMapsTheWelcomeScreenPath(): void
    {
        $user = $this->createUser();
        $hydrator = $this->createHydrator($user, 'Reporting/Dashboards/Dashboards');

        $this->assertSame('Reporting/Dashboards/Dashboards', $hydrator->hydrate($user)->getWelcomeScreenPath());
    }

    public function testHydrateMapsAMissingWelcomeScreenPathToNull(): void
    {
        $user = $this->createUser();
        $hydrator = $this->createHydrator($user, null);

        $this->assertNull($hydrator->hydrate($user)->getWelcomeScreenPath());
    }

    private function createUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'getId' => 7,
            'getUsername' => 'editor',
            'getClasses' => [],
            'getDocTypes' => [],
            'getWelcomeScreen' => true,
        ]);
    }

    private function createHydrator(UserInterface $expectedUser, ?string $welcomeScreenPath): UserInformationHydrator
    {
        return new UserInformationHydrator(
            $this->makeEmpty(ContentLanguagesHydratorInterface::class, ['hydrate' => []]),
            $this->makeEmpty(KeyBindingServiceInterface::class, ['hydrateKeyBindings' => []]),
            $this->makeEmpty(TwoFactorAuthHydratorInterface::class, [
                'hydrate' => new TwoFactorAuth(false, false, '', false),
            ]),
            $this->makeEmpty(UserPermissionServiceInterface::class, ['getAllowedUserPermissions' => []]),
            $this->makeEmpty(UserPerspectiveServiceInterface::class, [
                'getActivePerspective' => 'studio_default_perspective',
                'getAllowedPerspectives' => [],
            ]),
            $this->makeEmpty(UserSettingsServiceInterface::class, [
                'getWelcomeScreenPath' => function (UserInterface $user) use (
                    $expectedUser,
                    $welcomeScreenPath
                ): ?string {
                    $this->assertSame($expectedUser, $user);

                    return $welcomeScreenPath;
                },
            ]),
        );
    }
}
