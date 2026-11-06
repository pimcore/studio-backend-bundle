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
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserHydrator;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\WorkspaceHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PermissionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ObjectDependencies;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorAuth;
use Pimcore\Bundle\StudioBackendBundle\User\Service\KeyBindingServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserSettingsServiceInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserHydratorTest extends Unit
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
            'getName' => 'editor',
            'getClasses' => [],
            'getDocTypes' => [],
            'getPermissions' => [],
            'getRoles' => [],
            'getWebsiteTranslationLanguagesEdit' => [],
            'getWebsiteTranslationLanguagesView' => [],
            'getWelcomeScreen' => true,
        ]);
    }

    private function createHydrator(UserInterface $expectedUser, ?string $welcomeScreenPath): UserHydrator
    {
        return new UserHydrator(
            $this->makeEmpty(ContentLanguagesHydratorInterface::class, ['hydrate' => []]),
            $this->makeEmpty(WorkspaceHydratorInterface::class, [
                'hydrateAssetWorkspace' => [],
                'hydrateDataObjectWorkspace' => [],
                'hydrateDocumentWorkspace' => [],
            ]),
            $this->makeEmpty(ObjectDependenciesServiceInterface::class, [
                'getPreviewForUser' => new ObjectDependencies([], false, 0),
            ]),
            $this->makeEmpty(KeyBindingServiceInterface::class, ['hydrateKeyBindings' => []]),
            $this->makeEmpty(TwoFactorAuthHydratorInterface::class, [
                'hydrate' => new TwoFactorAuth(false, false, '', false),
            ]),
            $this->makeEmpty(UserPerspectiveServiceInterface::class, ['getConfigPerspectives' => []]),
            $this->makeEmpty(PermissionRepositoryInterface::class, ['getAvailablePermissions' => []]),
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
