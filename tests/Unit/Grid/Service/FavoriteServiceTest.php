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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Configuration\Share\Service\ConfigurationShareServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationFavoriteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\FavoriteService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;
use TypeError;
use function array_filter;
use function array_values;
use function spl_object_id;

/**
 * @internal
 */
final class FavoriteServiceTest extends Unit
{
    private const int USER_ID = 21;

    private const string CLASS_ID = '12';

    /**
     * @var array<string, GridConfigurationFavorite>
     */
    private array $storedFavorites = [];

    /**
     * Variants of two different objects of the same class must share the default of the user.
     */
    public function testDefaultSetOnOneObjectAppliesToAnotherObjectOfTheSameClass(): void
    {
        $template = $this->createConfiguration(1);
        $service = $this->createService();

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($template, 101235);

        $this->assertSame($template, $service->getFavoriteConfigurationForDataObject(101235, self::CLASS_ID));
        $this->assertSame($template, $service->getFavoriteConfigurationForDataObject(101238, self::CLASS_ID));
    }

    public function testObjectSpecificFavoriteTakesPrecedenceOverSharedDefault(): void
    {
        $shared = $this->createConfiguration(1);
        $specific = $this->createConfiguration(2);
        $service = $this->createService();

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($specific, 101238);
        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($shared, 101235);

        $this->assertSame($specific, $service->getFavoriteConfigurationForDataObject(101238, self::CLASS_ID));
        $this->assertSame($shared, $service->getFavoriteConfigurationForDataObject(101235, self::CLASS_ID));
        $this->assertSame($shared, $service->getFavoriteConfigurationForDataObject(999999, self::CLASS_ID));
    }

    public function testLastSetFavoriteBecomesSharedDefault(): void
    {
        $first = $this->createConfiguration(1);
        $second = $this->createConfiguration(2);
        $service = $this->createService();

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($first, 101235);
        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($second, 101238);

        $this->assertSame($second, $service->getFavoriteConfigurationForDataObject(999999, self::CLASS_ID));
    }

    public function testNoFavoriteAtAllResolvesToNull(): void
    {
        $this->assertNull($this->createService()->getFavoriteConfigurationForDataObject(101238, self::CLASS_ID));
    }

    public function testRemovingFavoriteAlsoRemovesSharedDefaultOfSameConfiguration(): void
    {
        $template = $this->createConfiguration(1);
        $service = $this->createService();

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($template, 101235);
        $service->removeDataObjectConfigurationAsFavoriteForCurrentUser($template, 101235);

        $this->assertNull($service->getFavoriteConfigurationForDataObject(101235, self::CLASS_ID));
        $this->assertNull($service->getFavoriteConfigurationForDataObject(101238, self::CLASS_ID));
    }

    public function testRemovingFavoriteKeepsSharedDefaultOfOtherConfiguration(): void
    {
        $shared = $this->createConfiguration(1);
        $specific = $this->createConfiguration(2);
        $service = $this->createService();

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($specific, 101238);
        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($shared, 101235);
        $service->removeDataObjectConfigurationAsFavoriteForCurrentUser($specific, 101238);

        $this->assertSame($shared, $service->getFavoriteConfigurationForDataObject(101238, self::CLASS_ID));
    }

    public function testSharedDefaultIsScopedToTheClass(): void
    {
        $service = $this->createService();

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($this->createConfiguration(1), 101235);

        $this->assertNull($service->getFavoriteConfigurationForDataObject(101238, '99'));
    }

    /**
     * A template that is saved together with its favorite has no id yet when the favorite is set.
     */
    public function testFavoriteCanBeSetOnConfigurationThatIsNotPersistedYet(): void
    {
        $service = $this->createService();
        $existing = $this->createConfiguration(1);
        $new = $this->createConfiguration(null);

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($existing, 101235);
        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($new, 101238);

        $this->assertSame($new, $service->getFavoriteConfigurationForDataObject(101238, self::CLASS_ID));
        $this->assertSame($new, $service->getFavoriteConfigurationForDataObject(999999, self::CLASS_ID));
        $this->assertSame($existing, $service->getFavoriteConfigurationForDataObject(101235, self::CLASS_ID));
    }

    /**
     * Two concurrent requests can both insert a shared default (the primary key includes the
     * configuration); the next time a favorite is set only the new default may remain.
     */
    public function testDuplicateSharedDefaultsAreCleanedUpWhenFavoriteIsSet(): void
    {
        $service = $this->createService();
        $first = $this->createConfiguration(1);
        $second = $this->createConfiguration(2);
        $third = $this->createConfiguration(3);

        foreach ([$first, $second] as $configuration) {
            $duplicate = new GridConfigurationFavorite();
            $duplicate->setUser(self::USER_ID);
            $duplicate->setFolder(0);
            $duplicate->setClassId(self::CLASS_ID);
            $duplicate->setConfiguration($configuration);
            $configuration->addFavorite($duplicate);
        }

        $service->setDataObjectConfigurationAsFavoriteForCurrentUser($third, 101235);

        $this->assertCount(1, $this->findFavorites(self::USER_ID, 0, self::CLASS_ID));
        $this->assertSame($third, $service->getFavoriteConfigurationForDataObject(999999, self::CLASS_ID));
    }

    private function createConfiguration(?int $id): GridConfiguration
    {
        $configuration = $this->getMockBuilder(GridConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getClassId', 'addFavorite', 'removeFavorite'])
            ->getMock();
        if ($id === null) {
            // mirrors the entity: the id is still null before the first flush
            $configuration->method('getId')->willThrowException(new TypeError('Return value must be of type int, null returned'));
        } else {
            $configuration->method('getId')->willReturn($id);
        }
        $configuration->method('getClassId')->willReturn(self::CLASS_ID);
        // The entity manager keeps a favorite only while its configuration holds it (orphanRemoval).
        $configuration->method('addFavorite')->willReturnCallback(
            function (GridConfigurationFavorite $favorite): void {
                $this->storedFavorites[spl_object_id($favorite)] = $favorite;
            }
        );
        $configuration->method('removeFavorite')->willReturnCallback(
            function (GridConfigurationFavorite $favorite): void {
                unset($this->storedFavorites[spl_object_id($favorite)]);
            }
        );

        return $configuration;
    }

    private function createService(): FavoriteService
    {
        $user = new User();
        $user->setId(self::USER_ID);

        $security = $this->createMock(SecurityServiceInterface::class);
        $security->method('getCurrentUser')->willReturn($user);

        $share = $this->createMock(ConfigurationShareServiceInterface::class);
        $share->method('isConfigurationSharedWithUser')->willReturn(true);

        $repository = $this->createMock(ConfigurationFavoriteRepositoryInterface::class);
        $repository->method('getByUserAndDataObject')->willReturnCallback(
            fn (int $user, int $folder, string $classId): ?GridConfigurationFavorite => $this->findFavorite(
                $user,
                $folder,
                $classId
            )
        );

        $repository->method('getAllByUserAndDataObject')->willReturnCallback(
            fn (int $user, int $folder, string $classId): array => $this->findFavorites($user, $folder, $classId)
        );

        return new FavoriteService($repository, $share, $security);
    }

    private function findFavorite(int $user, int $folder, string $classId): ?GridConfigurationFavorite
    {
        return $this->findFavorites($user, $folder, $classId)[0] ?? null;
    }

    /**
     * @return GridConfigurationFavorite[]
     */
    private function findFavorites(int $user, int $folder, string $classId): array
    {
        return array_values(array_filter(
            $this->storedFavorites,
            static fn (GridConfigurationFavorite $favorite): bool => $favorite->getUser() === $user
                && $favorite->getFolder() === $folder
                && $favorite->getClassId() === $classId
        ));
    }
}
