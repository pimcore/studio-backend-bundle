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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SubGroupVocabularyResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\ListingDatabaseStubTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function str_contains;

/**
 * @internal
 */
final class GroupServiceTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int GROUP_ID = 3;

    private const int OBJECT_ID = 5;

    private const int STORE_ID = 1;

    private const string GROUP_NAME = 'Technical';

    private const string OUTER = 'a-outer';

    private const string INNER = 'b-inner';

    private const string WEIGHT = 'c-weight';

    private const string RELATIONS_TABLE = 'classificationstore_relations';

    private const string FIELD_NAME = 'classificationStore';

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheTitleOfEveryKey(): void
    {
        $keyRelationResolver = $this->createRelationResolverReturning([
            11 => $this->createRelation(11, 'weight'),
            12 => $this->createRelation(12, 'height'),
            13 => $this->createRelation(13, 'depth'),
        ]);

        $keyGroupLayoutService = $this->createKeyGroupLayoutService([11 => 'Weight', 12 => null, 13 => '']);

        $keys = $this
            ->getGroupLayout($this->createService($keyRelationResolver, $keyGroupLayoutService))
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
        $groupLayout = $this->getGroupLayout($this->createService(
            $this->createMock(KeyRelationResolverInterface::class),
            $this->createMock(KeyGroupLayoutServiceInterface::class)
        ));

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
        $keyRelationResolver = $this->createRelationResolverReturning([
            13 => $this->createRelation(13, 'depth'),
            11 => $this->createRelation(11, 'weight'),
            12 => $this->createRelation(12, 'height'),
        ]);

        $keys = $this
            ->getGroupLayout($this->createService($keyRelationResolver, $this->createKeyGroupLayoutService()))
            ->getKeys();

        $this->assertSame([13, 11, 12], array_map(static fn (KeyLayout $key): int => $key->getId(), $keys));
    }

    /**
     * @throws Exception
     */
    public function testGroupLayoutCarriesTheSubGroupsOfItsKeys(): void
    {
        $this->stubRelationsReturning([
            $this->createRelationRow(keyId: 11, sorter: 0, subGroup: self::OUTER),
            $this->createRelationRow(keyId: 12, sorter: 1, subGroup: self::INNER),
        ]);

        $groupLayout = $this->getGroupLayout($this->createService(
            $this->createKeyRelationResolver([11 => self::OUTER, 12 => self::INNER]),
            $this->createKeyGroupLayoutService(),
            $this->createVocabularyResolver()
        ));

        $this->assertSame([self::OUTER, self::INNER], $this->getSubGroupIdentifiers($groupLayout));
        $this->assertSame('Outer dimensions', $groupLayout->getSubGroups()[0]->getName());
        $this->assertSame('The a-outer sub group', $groupLayout->getSubGroups()[0]->getDescription());
        $this->assertSame(
            [self::OUTER, self::INNER],
            array_map(static fn (KeyLayout $key): ?string => $key->getSubGroup(), $groupLayout->getKeys())
        );
    }

    /**
     * The sorter order of these relations disagrees with both the alphabetical order of the
     * identifiers and the order of the key ids, so only the derived one can produce this result.
     *
     * @throws Exception
     */
    public function testSubGroupsFollowTheOrderDerivedFromTheSorter(): void
    {
        $this->stubRelationsReturning([
            $this->createRelationRow(keyId: 30, sorter: 0, subGroup: self::WEIGHT),
            $this->createRelationRow(keyId: 10, sorter: 1, subGroup: self::OUTER),
            $this->createRelationRow(keyId: 20, sorter: 2, subGroup: self::INNER),
        ]);

        $groupLayout = $this->getGroupLayout($this->createService(
            $this->createKeyRelationResolver([30 => self::WEIGHT, 10 => self::OUTER, 20 => self::INNER]),
            $this->createKeyGroupLayoutService(),
            $this->createVocabularyResolver()
        ));

        $this->assertStringEndsWith(
            ' ORDER BY `sorter` ASC, `id` ASC',
            $this->getExecutedQueries()[0]
        );
        $this->assertSame(
            [self::WEIGHT, self::OUTER, self::INNER],
            $this->getSubGroupIdentifiers($groupLayout)
        );
    }

    /**
     * @throws Exception
     */
    public function testASubGroupWithNoKeyInTheGroupIsAbsent(): void
    {
        $this->stubRelationsReturning([
            $this->createRelationRow(keyId: 11, sorter: 0, subGroup: self::INNER),
        ]);

        $groupLayout = $this->getGroupLayout($this->createService(
            $this->createKeyRelationResolver([11 => self::INNER]),
            $this->createKeyGroupLayoutService(),
            $this->createVocabularyResolver()
        ));

        $this->assertSame([self::INNER], $this->getSubGroupIdentifiers($groupLayout));
    }

    /**
     * @throws Exception
     */
    private function getGroupLayout(GroupService $groupService): GroupLayout
    {
        return $groupService->getLayoutDefinition(
            self::GROUP_ID,
            new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID)
        );
    }

    /**
     * @return string[]
     */
    private function getSubGroupIdentifiers(GroupLayout $groupLayout): array
    {
        return array_map(
            static fn (SubGroupLayout $subGroup): string => $subGroup->getIdentifier(),
            $groupLayout->getSubGroups()
        );
    }

    /**
     * Only the relations of the group are answered; the key configs the relation cache reads next
     * are not what this asserts on.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function stubRelationsReturning(array $rows): void
    {
        $this->stubDatabaseAnswering(
            static fn (string $query): array => str_contains($query, self::RELATIONS_TABLE) ? $rows : []
        );
    }

    /**
     * @param array<int, string> $subGroupsByKeyId
     *
     * @throws Exception
     */
    private function createKeyRelationResolver(array $subGroupsByKeyId): KeyRelationResolverInterface
    {
        $relations = [];
        foreach ($subGroupsByKeyId as $keyId => $subGroup) {
            $relation = $this->createRelation($keyId, 'key' . $keyId);
            $relation->setSubGroup($subGroup);
            $relations[$keyId] = $relation;
        }

        return $this->createRelationResolverReturning($relations);
    }

    /**
     * @param array<int, KeyGroupRelation> $relations
     *
     * @throws Exception
     */
    private function createRelationResolverReturning(array $relations): KeyRelationResolverInterface
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        return $keyRelationResolver;
    }

    /**
     * @param array<int, string|null>|null $titles
     *
     * @throws Exception
     */
    private function createKeyGroupLayoutService(?array $titles = null): KeyGroupLayoutServiceInterface
    {
        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());

        if ($titles !== null) {
            $keyGroupLayoutService
                ->method('getKeyTitle')
                ->willReturnCallback(static fn (int $keyId): ?string => $titles[$keyId]);
        }

        return $keyGroupLayoutService;
    }

    /**
     * @throws Exception
     */
    private function createVocabularyResolver(): SubGroupVocabularyResolverInterface
    {
        $names = [
            self::OUTER => 'Outer dimensions',
            self::INNER => 'Inner dimensions',
            self::WEIGHT => 'Shipping weight',
        ];

        $vocabulary = [];
        foreach ($names as $identifier => $name) {
            $config = new SubGroupConfig();
            $config->setStoreId(self::STORE_ID);
            $config->setName($name);
            $config->setDescription('The ' . $identifier . ' sub group');
            $config->setIdentifier($identifier);
            $vocabulary[$identifier] = $config;
        }

        $vocabularyResolver = $this->createMock(SubGroupVocabularyResolverInterface::class);
        $vocabularyResolver
            ->method('getByStore')
            ->with(self::STORE_ID)
            ->willReturn($vocabulary);

        return $vocabularyResolver;
    }

    /**
     * @return array<string, mixed>
     */
    private function createRelationRow(int $keyId, int $sorter, string $subGroup): array
    {
        return [
            'keyId' => $keyId,
            'groupId' => self::GROUP_ID,
            'sorter' => $sorter,
            'name' => 'key' . $keyId,
            'description' => '',
            'type' => 'input',
            'definition' => '{"fieldtype":"input"}',
            'enabled' => 1,
            'mandatory' => 0,
            'subGroup' => $subGroup,
        ];
    }

    /**
     * @throws Exception
     */
    private function createService(
        KeyRelationResolverInterface $keyRelationResolver,
        KeyGroupLayoutServiceInterface $keyGroupLayoutService,
        ?SubGroupVocabularyResolverInterface $subGroupVocabularyResolver = null
    ): GroupService {
        $concreteObjectResolver = $this->createMock(ConcreteObjectResolverInterface::class);
        $concreteObjectResolver
            ->method('getById')
            ->with(self::OBJECT_ID)
            ->willReturn($this->createMock(Concrete::class));

        $group = new GroupConfig();
        $group->setId(self::GROUP_ID);
        $group->setName(self::GROUP_NAME);
        $group->setDescription('Technical attributes');
        $group->setStoreId(self::STORE_ID);

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
            new KeyGroupRelationCache(),
            $subGroupVocabularyResolver ?? $this->createMock(SubGroupVocabularyResolverInterface::class),
        );
    }

    private function createRelation(int $keyId, string $name): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName($name);
        $relation->setDescription('');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');

        return $relation;
    }
}
