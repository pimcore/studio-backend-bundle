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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\CollectionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionRelationsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\CollectionLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\CollectionService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function array_values;

/**
 * @internal
 */
final class CollectionServiceTest extends Unit
{
    private const int COLLECTION_ID = 9;

    private const int OBJECT_ID = 5;

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
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
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
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
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
            ->getLayoutDefinition(self::COLLECTION_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
            ->getGroups();

        $this->assertSame([4, 3], array_map(static fn (GroupLayout $group): int => $group->getId(), $groups));
    }

    private function createRelation(int $groupId): CollectionGroupRelation
    {
        $relation = new CollectionGroupRelation();
        $relation->setGroupId($groupId);
        $relation->setColId(self::COLLECTION_ID);

        return $relation;
    }
}
