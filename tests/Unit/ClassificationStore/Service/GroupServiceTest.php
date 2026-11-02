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
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_flip;
use function array_intersect_key;
use function array_map;

/**
 * @internal
 */
final class GroupServiceTest extends Unit
{
    private const int GROUP_ID = 3;

    private const int OBJECT_ID = 5;

    private const string GROUP_NAME = 'Technical';

    private const string SUB_GROUP_A = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SUB_GROUP_B = '01JBQ8X9Z0ABCDEFGHJKMNPQRT';

    private const string NAME_DIMENSIONS = 'Dimensions';

    private const string NAME_APPEARANCE = 'Appearance';

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheTitleOfEveryKey(): void
    {
        $titles = [11 => 'Weight', 12 => null, 13 => ''];

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn([
                11 => $this->createRelation(11, 'weight'),
                12 => $this->createRelation(12, 'height'),
                13 => $this->createRelation(13, 'depth'),
            ]);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());
        $keyGroupLayoutService
            ->method('getKeyTitle')
            ->willReturnCallback(static fn (int $keyId): ?string => $titles[$keyId]);

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
            ->getKeys();

        $this->assertCount(3, $keys);
        $this->assertSame('Weight', $keys[0]->getTitle());
        $this->assertNull($keys[1]->getTitle());
        $this->assertSame('', $keys[2]->getTitle());
    }

    /**
     * A group added in the session is served by this endpoint and has to report the same shape as a
     * pre-loaded one, so it carries a title too. classificationstore_groups has no title column, so
     * the title is the group name until package 16 lands.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheGroupTitle(): void
    {
        $groupLayout = $this
            ->createService(
                $this->createMock(KeyRelationResolverInterface::class),
                $this->createMock(KeyGroupLayoutServiceInterface::class)
            )
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID));

        $this->assertSame(self::GROUP_NAME, $groupLayout->getTitle());

        $attributes = (new ReflectionClass(GroupLayout::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'description', 'keys'],
            $attributes[0]->getArguments()['required']
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionKeepsRelationOrder(): void
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn([
                13 => $this->createRelation(13, 'depth'),
                11 => $this->createRelation(11, 'weight'),
                12 => $this->createRelation(12, 'height'),
            ]);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
            ->getKeys();

        $this->assertSame([13, 11, 12], array_map(static fn (KeyLayout $key): int => $key->getId(), $keys));
    }

    /**
     * @throws Exception
     */
    public function testEveryKeyCarriesItsRawSubGroupIdentifier(): void
    {
        $layout = $this->getLayoutWith(
            [
                11 => $this->createRelation(11, 'weight', self::SUB_GROUP_A),
                12 => $this->createRelation(12, 'height'),
            ],
            [self::SUB_GROUP_A => $this->createSubGroupConfig(self::SUB_GROUP_A, self::NAME_DIMENSIONS)]
        );

        $this->assertSame(self::SUB_GROUP_A, $layout->getKeys()[0]->getSubGroup());
        $this->assertNull($layout->getKeys()[1]->getSubGroup());
    }

    /**
     * @throws Exception
     */
    public function testSubGroupsAreResolvedFromTheStoresVocabulary(): void
    {
        $layout = $this->getLayoutWith(
            [11 => $this->createRelation(11, 'weight', self::SUB_GROUP_A)],
            [self::SUB_GROUP_A => $this->createSubGroupConfig(self::SUB_GROUP_A, self::NAME_DIMENSIONS)]
        );

        $subGroups = $layout->getSubGroups();

        $this->assertCount(1, $subGroups);
        $this->assertSame(self::SUB_GROUP_A, $subGroups[0]->getIdentifier());
        $this->assertSame(self::NAME_DIMENSIONS, $subGroups[0]->getName());
        $this->assertSame('Description of ' . self::NAME_DIMENSIONS, $subGroups[0]->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testSubGroupsIsEmptyWhenNoKeyIsTagged(): void
    {
        $layout = $this->getLayoutWith([11 => $this->createRelation(11, 'weight')], []);

        $this->assertSame([], $layout->getSubGroups());
    }

    /**
     * The order is derived from the relations, which arrive in sorter then id order, so a sub group
     * sits where its first key sits. Neither the alphabetical nor the id order would give this.
     *
     * @throws Exception
     */
    public function testSubGroupOrderFollowsTheFirstKeyOfEachSubGroup(): void
    {
        $layout = $this->getLayoutWith(
            [
                // sorter 0, 1, 2, 3 — B's first key comes before A's
                14 => $this->createRelation(14, 'depth', self::SUB_GROUP_B),
                13 => $this->createRelation(13, 'weight', self::SUB_GROUP_A),
                12 => $this->createRelation(12, 'height', self::SUB_GROUP_B),
                11 => $this->createRelation(11, 'colour', self::SUB_GROUP_A),
            ],
            [
                self::SUB_GROUP_A => $this->createSubGroupConfig(self::SUB_GROUP_A, self::NAME_APPEARANCE),
                self::SUB_GROUP_B => $this->createSubGroupConfig(self::SUB_GROUP_B, self::NAME_DIMENSIONS),
            ]
        );

        // 'Dimensions' before 'Appearance': not alphabetical, and not by vocabulary id either
        $this->assertSame(
            [self::NAME_DIMENSIONS, self::NAME_APPEARANCE],
            array_map(static fn (SubGroupLayout $subGroup): string => $subGroup->getName(), $layout->getSubGroups())
        );
    }

    /**
     * @throws Exception
     */
    public function testADanglingSubGroupIdentifierAddsNoVocabularyEntry(): void
    {
        $layout = $this->getLayoutWith(
            [11 => $this->createRelation(11, 'weight', self::SUB_GROUP_A)],
            // the vocabulary of the store does not carry the identifier the key is tagged with
            []
        );

        $this->assertSame(self::SUB_GROUP_A, $layout->getKeys()[0]->getSubGroup());
        $this->assertSame([], $layout->getSubGroups());
    }

    /**
     * objectId is optional, the way layout-by-key has it: the column picker asks for a layout with
     * no object in context and gets the definitions unenriched.
     *
     * @throws Exception
     */
    public function testAnAbsentObjectIdYieldsTheLayoutWithANullObject(): void
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->willReturn([11 => $this->createRelation(11, 'weight')]);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->expects($this->once())
            ->method('getLayoutDefinition')
            ->with($this->anything(), 'classificationStore', null)
            ->willReturn(new Input());

        $layout = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore'));

        $this->assertCount(1, $layout->getKeys());
    }

    /**
     * An objectId that does not resolve is still a 404 — only its absence is tolerated.
     *
     * @throws Exception
     */
    public function testAnObjectIdThatDoesNotResolveIsStillNotFound(): void
    {
        $concreteObjectResolver = $this->createMock(ConcreteObjectResolverInterface::class);
        $concreteObjectResolver
            ->method('getById')
            ->willReturn(null);

        $service = $this->createService(
            $this->createMock(KeyRelationResolverInterface::class),
            $this->createMock(KeyGroupLayoutServiceInterface::class),
            $concreteObjectResolver
        );

        $this->expectException(NotFoundException::class);

        $service->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', 999999));
    }

    /**
     * @param KeyGroupRelation[] $relations
     * @param array<string, SubGroupConfig> $vocabulary
     *
     * @throws Exception
     */
    private function getLayoutWith(array $relations, array $vocabulary): GroupLayout
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository
            ->method('getByIdentifiers')
            ->willReturnCallback(
                static fn (int $storeId, array $identifiers): array => array_intersect_key(
                    $vocabulary,
                    array_flip($identifiers)
                )
            );

        return $this
            ->createService($keyRelationResolver, $keyGroupLayoutService, null, $subGroupRepository)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID));
    }

    /**
     * @throws Exception
     */
    private function createService(
        KeyRelationResolverInterface $keyRelationResolver,
        KeyGroupLayoutServiceInterface $keyGroupLayoutService,
        ?ConcreteObjectResolverInterface $concreteObjectResolver = null,
        ?SubGroupRepositoryInterface $subGroupRepository = null,
    ): GroupService {
        if (!$concreteObjectResolver) {
            $concreteObjectResolver = $this->createMock(ConcreteObjectResolverInterface::class);
            $concreteObjectResolver
                ->method('getById')
                ->with(self::OBJECT_ID)
                ->willReturn($this->createMock(Concrete::class));
        }

        $group = new GroupConfig();
        $group->setId(self::GROUP_ID);
        $group->setName(self::GROUP_NAME);
        $group->setDescription('Technical attributes');

        $groupConfigRepository = $this->createMock(GroupConfigRepositoryInterface::class);
        $groupConfigRepository
            ->method('getById')
            ->with(self::GROUP_ID)
            ->willReturn($group);

        return new GroupService(
            $concreteObjectResolver,
            $groupConfigRepository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(GroupHydratorInterface::class),
            $keyRelationResolver,
            $keyGroupLayoutService,
            new GroupLayoutHydrator(),
            $this->createMock(ClassDefinitionResolverInterface::class),
            $subGroupRepository ?? $this->createMock(SubGroupRepositoryInterface::class),
        );
    }

    private function createRelation(int $keyId, string $name, ?string $subGroup = null): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName($name);
        $relation->setDescription('');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');
        $relation->setSubGroup($subGroup);

        return $relation;
    }

    private function createSubGroupConfig(string $identifier, string $name): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setName($name);
        $config->setDescription('Description of ' . $name);
        $config->setIdentifier($identifier);

        return $config;
    }
}
