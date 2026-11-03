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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\CollectionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionRelationsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\CollectionLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\CollectionService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_keys;
use function array_map;
use function array_values;
use function sprintf;

/**
 * @internal
 */
final class CollectionServiceTest extends Unit
{
    use LabelResolverMockTrait;

    private const int COLLECTION_ID = 9;

    private const int OBJECT_ID = 5;

    private const string FIELD_NAME = 'classificationStore';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    /**
     * @var array<int, list<int>> the keys of each group this collection wraps
     */
    private const array GROUP_KEY_IDS = [3 => [11, 12], 4 => [13]];

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionKeepsKeyTitlesOfEveryGroup(): void
    {
        $definition = new Input();
        $groupLayouts = [
            3 => new GroupLayout(3, 'Technical', 'Technical attributes', [
                new KeyLayout(11, 'weight', '', $definition, 'Weight'),
                new KeyLayout(12, 'height', '', $definition),
            ]),
            4 => new GroupLayout(4, 'Marketing', 'Marketing attributes', [
                new KeyLayout(13, 'claim', '', $definition, ''),
            ]),
        ];

        $collectionRelationsRepository = $this->createMock(CollectionRelationsRepositoryInterface::class);
        $collectionRelationsRepository
            ->method('getFromCollection')
            ->with(self::COLLECTION_ID)
            ->willReturn([$this->createRelation(3), $this->createRelation(4)]);

        $groupService = $this->createMock(GroupServiceInterface::class);
        $groupService
            ->method('getLayoutDefinition')
            ->willReturnCallback(static fn (int $groupId): GroupLayout => $groupLayouts[$groupId]);

        $service = new CollectionService(
            $collectionRelationsRepository,
            $this->createMock(CollectionConfigRepositoryInterface::class),
            $this->createMock(CollectionHydratorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $groupService,
        );

        $groups = $service
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getGroups();

        $this->assertCount(2, $groups);
        $this->assertSame('Weight', $groups[0]->getKeys()[0]->getTitle());
        $this->assertNull($groups[0]->getKeys()[1]->getTitle());
        $this->assertSame('', $groups[1]->getKeys()[0]->getTitle());
    }

    /**
     * The collection response is a pure wrapper: once every group it wraps carries a title, it exposes
     * a title for everything in it that has one, so the collection itself gains no field of its own.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesATitleForEveryGroupAndStaysAWrapper(): void
    {
        $collectionRelationsRepository = $this->createMock(CollectionRelationsRepositoryInterface::class);
        $collectionRelationsRepository
            ->method('getFromCollection')
            ->with(self::COLLECTION_ID)
            ->willReturn([$this->createRelation(3), $this->createRelation(4)]);

        $titles = [3 => 'Technical', 4 => 'Marketing'];

        $groupService = $this->createMock(GroupServiceInterface::class);
        $groupService
            ->method('getLayoutDefinition')
            ->willReturnCallback(
                static fn (int $groupId): GroupLayout => new GroupLayout(
                    $groupId,
                    $titles[$groupId],
                    'Description',
                    [],
                    $titles[$groupId]
                )
            );

        $service = new CollectionService(
            $collectionRelationsRepository,
            $this->createMock(CollectionConfigRepositoryInterface::class),
            $this->createMock(CollectionHydratorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $groupService,
        );

        $groups = $service
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getGroups();

        $this->assertSame(array_values($titles), array_map(
            static fn (GroupLayout $group): ?string => $group->getTitle(),
            $groups
        ));

        $parameters = (new ReflectionClass(CollectionLayout::class))->getConstructor()?->getParameters() ?? [];

        $this->assertCount(1, $parameters);
        $this->assertSame('groups', $parameters[0]->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionKeepsRelationOrder(): void
    {
        $collectionRelationsRepository = $this->createMock(CollectionRelationsRepositoryInterface::class);
        $collectionRelationsRepository
            ->method('getFromCollection')
            ->with(self::COLLECTION_ID)
            ->willReturn([$this->createRelation(4), $this->createRelation(3)]);

        $groupService = $this->createMock(GroupServiceInterface::class);
        $groupService
            ->method('getLayoutDefinition')
            ->willReturnCallback(
                static fn (int $groupId): GroupLayout => new GroupLayout($groupId, 'Group', 'Description', [])
            );

        $service = new CollectionService(
            $collectionRelationsRepository,
            $this->createMock(CollectionConfigRepositoryInterface::class),
            $this->createMock(CollectionHydratorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $groupService,
        );

        $groups = $service
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getGroups();

        $this->assertSame([4, 3], array_map(static fn (GroupLayout $group): int => $group->getId(), $groups));
    }

    /**
     * The collection endpoint walks the groups it wraps, so it inherits the group endpoint's one
     * label query per group - two groups, two preloads, each carrying only its own keys.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionPreloadsOncePerGroupItWalks(): void
    {
        $collectionRelationsRepository = $this->createMock(CollectionRelationsRepositoryInterface::class);
        $collectionRelationsRepository
            ->method('getFromCollection')
            ->with(self::COLLECTION_ID)
            ->willReturn(array_map(
                fn (int $groupId): CollectionGroupRelation => $this->createRelation($groupId),
                array_keys(self::GROUP_KEY_IDS)
            ));

        $service = new CollectionService(
            $collectionRelationsRepository,
            $this->createMock(CollectionConfigRepositoryInterface::class),
            $this->createMock(CollectionHydratorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createGroupServiceWithRealKeyLabels(),
        );

        $groups = $service
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getGroups();

        $this->assertSame(array_values(self::GROUP_KEY_IDS), $this->preloadedIds());
        $this->assertCount(2, $groups);
    }

    /**
     * @throws Exception
     */
    private function createGroupServiceWithRealKeyLabels(): GroupService
    {
        $relationsByGroup = [];
        $keyConfigs = [];
        foreach (self::GROUP_KEY_IDS as $groupId => $keyIds) {
            foreach ($keyIds as $keyId) {
                $keyName = sprintf('key%d', $keyId);
                $relation = new KeyGroupRelation();
                $relation->setKeyId($keyId);
                $relation->setGroupId($groupId);
                $relation->setName($keyName);
                $relation->setDescription('');
                $relation->setDefinition('{"fieldtype":"input"}');
                $relation->setType('input');

                $relationsByGroup[$groupId][$keyId] = $relation;
                $keyConfigs[$keyId] = $this->createKeyConfig($keyId, $keyName);
            }
        }

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->willReturnCallback(static fn (int $groupId): array => $relationsByGroup[$groupId]);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturn(new Input());

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        $concreteObjectResolver = $this->createMock(ConcreteObjectResolverInterface::class);
        $concreteObjectResolver
            ->method('getById')
            ->with(self::OBJECT_ID)
            ->willReturn($this->createMock(Concrete::class));

        $groupConfigRepository = $this->createMock(GroupConfigRepositoryInterface::class);
        $groupConfigRepository
            ->method('getById')
            ->willReturnCallback(static function (int $groupId): GroupConfig {
                $group = new GroupConfig();
                $group->setId($groupId);
                $group->setName(sprintf('Group %d', $groupId));

                return $group;
            });

        return new GroupService(
            $concreteObjectResolver,
            $groupConfigRepository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(GroupHydratorInterface::class),
            $keyRelationResolver,
            new KeyGroupLayoutService(
                $serviceResolver,
                $keyRelationResolver,
                new ConcreteObjectResolver(),
                $keyConfigResolver,
                $this->createLabelResolver(),
                $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
            ),
            new GroupLayoutHydrator(),
            $this->createMock(ClassDefinitionResolverInterface::class),
            $this->createMock(SubGroupRepositoryInterface::class),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
        );
    }

    private function createRelation(int $groupId): CollectionGroupRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setGroupId($groupId);
        $relation->setColId(self::COLLECTION_ID);

        return $relation;
    }
}
