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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupRelationService;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The store-wide key listing the object editor's add-keys modal and the grid column picker read.
 * A key's `localized` flag is a column of the key, and core's relation listing keeps only the joined
 * columns it has a property for - it has none for this one - so the flag is read off the key
 * configuration per row instead.
 *
 * @internal
 */
final class KeyGroupRelationServiceTest extends Unit
{
    private const int STORE_ID = 1;

    private const int GROUP_ID = 7;

    private const int LOCALIZED_KEY_ID = 11;

    private const int NOT_LOCALIZED_KEY_ID = 12;

    private const int INHERITING_KEY_ID = 13;

    private const int UNRESOLVABLE_KEY_ID = 14;

    /**
     * @throws Exception
     */
    public function testEveryListedRelationCarriesTheLocalizationFlagOfItsKey(): void
    {
        $relations = $this->getKeyGroupRelations();

        $this->assertCount(4, $relations);
        $this->assertTrue($relations[0]->getLocalized());
        $this->assertFalse($relations[1]->getLocalized());
        $this->assertNull($relations[2]->getLocalized());
    }

    /**
     * A relation whose key the configuration no longer carries reports nothing, rather than taking
     * the whole listing down with it.
     *
     * @throws Exception
     */
    public function testARelationWhoseKeyCannotBeResolvedReportsNoLocalizationFlag(): void
    {
        $this->assertNull($this->getKeyGroupRelations()[3]->getLocalized());
    }

    /**
     * @return KeyGroupRelation[]
     *
     * @throws Exception
     */
    private function getKeyGroupRelations(): array
    {
        $keyConfigs = [
            self::LOCALIZED_KEY_ID => $this->createKeyConfig(self::LOCALIZED_KEY_ID),
            self::NOT_LOCALIZED_KEY_ID => $this->createKeyConfig(self::NOT_LOCALIZED_KEY_ID),
            self::INHERITING_KEY_ID => $this->createKeyConfig(self::INHERITING_KEY_ID),
        ];

        $keyLocalizationResolver = new InMemoryKeyLocalizationResolver();
        $keyLocalizationResolver->setLocalized($keyConfigs[self::LOCALIZED_KEY_ID], true);
        $keyLocalizationResolver->setLocalized($keyConfigs[self::NOT_LOCALIZED_KEY_ID], false);

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('getPaginatedKeyGroupRelationByStore')
            ->willReturn([
                $this->createRelation(self::LOCALIZED_KEY_ID),
                $this->createRelation(self::NOT_LOCALIZED_KEY_ID),
                $this->createRelation(self::INHERITING_KEY_ID),
                $this->createRelation(self::UNRESOLVABLE_KEY_ID),
            ]);

        $groupService = $this->createMock(GroupServiceInterface::class);
        $groupService
            ->method('getAllowedGroupIds')
            ->willReturn([]);

        $groupConfig = new GroupConfig();
        $groupConfig->setId(self::GROUP_ID);
        $groupConfig->setName('Measurements');

        $groupConfigResolver = $this->createMock(GroupConfigResolverInterface::class);
        $groupConfigResolver
            ->method('getById')
            ->willReturn($groupConfig);

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        $service = new KeyGroupRelationService(
            $repository,
            $groupService,
            new KeyGroupRelationHydrator(),
            $groupConfigResolver,
            $this->createMock(EventDispatcherInterface::class),
            $keyConfigResolver,
            $keyLocalizationResolver,
        );

        return $service
            ->getKeyGroupRelations(
                new ListClassificationStoreParameter(self::STORE_ID, 'classificationStore', 1, 50)
            )
            ->getItems();
    }

    private function createKeyConfig(int $keyId): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId($keyId);
        $config->setStoreId(self::STORE_ID);
        $config->setName('key' . $keyId);
        $config->setType('input');

        return $config;
    }

    private function createRelation(int $keyId): CoreKeyGroupRelation
    {
        $relation = new CoreKeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName('key' . $keyId);
        $relation->setDescription('');

        return $relation;
    }
}
