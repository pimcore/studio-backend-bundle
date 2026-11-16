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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ProvidedOptionNarrower;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SubGroupLayoutResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function json_encode;
use function sprintf;
use Psr\Log\NullLogger;

/**
 * A group is laid out the same whether it was already assigned when the object opened - the object
 * layout, which core builds and this bundle enriches in place - or was added through "Add group",
 * which the classification store layout endpoint serves. Core's payload carries the group, its keys
 * and their definitions and nothing else, so everything the editor renders sub group sections and
 * variant editors from is added here; a divergence fails rather than being noticed in the editor
 * once a group has been deleted and added back.
 *
 * @internal
 */
final class GroupLayoutPathAgreementTest extends Unit
{
    use LabelResolverMockTrait;

    private const int GROUP_ID = 3;

    private const int STORE_ID = 7;

    private const string FIELD_NAME = 'classificationStore';

    private const string GROUP_NAME = 'Technical';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const string SUB_GROUP_A = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SUB_GROUP_B = '01JBQ8X9Z0ABCDEFGHJKMNPQRT';

    /** What the enriched payload has to say about a key, beside the title and the definition. */
    private const array KEY_MEMBERS = [
        'subGroup',
        'variants',
        'multiValue',
        'maximum',
        'defaultVariant',
        'defaultVariantLabel',
    ];

    private const array MULTI_VALUE_DEFINITION = [
        'fieldtype' => MultiValueServiceResolver::MULTI_VALUE_TYPE,
        'variants' => [['name' => 'metric', 'label' => 'Metric'], 'imperial'],
        'multiValue' => true,
        'maximum' => 3,
        'unqualifiedValue' => false,
        'unqualifiedValueLabel' => 'Standard',
    ];

    private const string INPUT_TYPE = 'input';

    private const array INPUT_DEFINITION = ['fieldtype' => self::INPUT_TYPE];

    /**
     * @throws Exception
     */
    public function testBothPathsPublishTheSameSubGroupsForTheGroup(): void
    {
        [$groupLayout, $fieldDefinition] = $this->layoutFromBothPaths($this->createTaggedRelations());

        // two identifiers are carried, and the one the vocabulary does not know is left out by both
        $this->assertCount(1, $groupLayout->getSubGroups());
        $this->assertSame(self::SUB_GROUP_A, $groupLayout->getSubGroups()[0]->getIdentifier());
        $this->assertEquals($groupLayout->getSubGroups(), $this->enrichedGroup($fieldDefinition)['subGroups']);
    }

    /**
     * @throws Exception
     */
    public function testBothPathsPublishTheSameSubGroupAndQualifiersForEveryKey(): void
    {
        [$groupLayout, $fieldDefinition] = $this->layoutFromBothPaths($this->createTaggedRelations());

        $published = $this->membersOfLayout($groupLayout);

        // the fixture declares something on every member, so an empty answer cannot pass this
        $this->assertSame(self::SUB_GROUP_A, $published[0]['subGroup']);
        $this->assertSame(3, $published[0]['maximum']);
        $this->assertNotEmpty($published[0]['variants']);
        $this->assertSame('Standard', $published[0]['defaultVariantLabel']);

        $this->assertEquals($published, $this->membersOfPayload($fieldDefinition));
    }

    /**
     * A group whose keys are tagged with nothing and declare nothing is laid out identically by both
     * paths too: an empty sub group list and null on every key member, never [] and false.
     *
     * @throws Exception
     */
    public function testBothPathsAgreeOnAGroupThatDeclaresNothing(): void
    {
        [$groupLayout, $fieldDefinition] = $this->layoutFromBothPaths([
            11 => $this->createRelation(11, self::INPUT_TYPE),
            12 => $this->createRelation(12, self::INPUT_TYPE),
        ]);

        $this->assertSame([], $groupLayout->getSubGroups());
        $this->assertSame([], $this->enrichedGroup($fieldDefinition)['subGroups']);
        $this->assertEquals($this->membersOfLayout($groupLayout), $this->membersOfPayload($fieldDefinition));
    }

    /**
     * @throws Exception
     */
    public function testBothPathsPublishTheSameGroupTitle(): void
    {
        [$groupLayout, $fieldDefinition] = $this->layoutFromBothPaths($this->createTaggedRelations());

        $this->assertSame(self::GROUP_NAME, $groupLayout->getTitle());
        $this->assertSame($groupLayout->getTitle(), $this->enrichedGroup($fieldDefinition)['title']);
    }

    /**
     * The one group, laid out by the endpoint and by the enricher, off the same relations, the same
     * key configurations and the same vocabulary.
     *
     * @param KeyGroupRelation[] $relations keyed by key id, in relation order
     *
     * @return array{0: GroupLayout, 1: Classificationstore}
     *
     * @throws Exception
     */
    private function layoutFromBothPaths(array $relations): array
    {
        $keyConfigs = $this->createKeyConfigs($relations);
        $keyRelationResolver = $this->createKeyRelationResolver($relations);
        $subGroupLayoutResolver = new SubGroupLayoutResolver($this->createSubGroupRepository());
        $keyGroupLayoutService = $this->createKeyGroupLayoutService($keyConfigs, $keyRelationResolver);

        $groupLayout = $this
            ->createGroupService($keyRelationResolver, $keyGroupLayoutService, $subGroupLayoutResolver)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME));

        $fieldDefinition = $this->createFieldDefinition($relations);
        $enricher = new ActiveGroupTitleEnricher(
            $this->createLabelResolver(),
            $this->createDefinitionCacheResolver($keyConfigs),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
            $keyRelationResolver,
            $keyGroupLayoutService,
            $subGroupLayoutResolver
        );
        $enricher->enrich($fieldDefinition);

        return [$groupLayout, $fieldDefinition];
    }

    /**
     * Key 11 declares qualified values and sits in a sub group, key 12 sits in one the vocabulary of
     * the store does not carry, key 13 sits in none and declares nothing.
     *
     * @return KeyGroupRelation[]
     */
    private function createTaggedRelations(): array
    {
        return [
            11 => $this->createRelation(11, MultiValueServiceResolver::MULTI_VALUE_TYPE, self::SUB_GROUP_A),
            12 => $this->createRelation(12, self::INPUT_TYPE, self::SUB_GROUP_B),
            13 => $this->createRelation(13, self::INPUT_TYPE),
        ];
    }

    private function createRelation(int $keyId, string $type, ?string $subGroup = null): KeyGroupRelation
    {
        $definition = $type === MultiValueServiceResolver::MULTI_VALUE_TYPE
            ? self::MULTI_VALUE_DEFINITION
            : self::INPUT_DEFINITION;

        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(sprintf('key%d', $keyId));
        $relation->setDescription('');
        $relation->setDefinition((string) json_encode($definition));
        $relation->setType($type);
        $relation->setSubGroup($subGroup);

        return $relation;
    }

    /**
     * @param KeyGroupRelation[] $relations
     *
     * @return array<int, KeyConfig>
     */
    private function createKeyConfigs(array $relations): array
    {
        $keyConfigs = [];

        foreach ($relations as $keyId => $relation) {
            $keyConfig = $this->createKeyConfig($keyId, $relation->getName());
            $keyConfig->setType($relation->getType());
            $keyConfig->setDefinition($relation->getDefinition());
            $keyConfigs[$keyId] = $keyConfig;
        }

        return $keyConfigs;
    }

    /**
     * @param KeyGroupRelation[] $relations
     *
     * @throws Exception
     */
    private function createKeyRelationResolver(array $relations): KeyRelationResolverInterface
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);
        $keyRelationResolver
            ->method('getByGroups')
            ->willReturn([self::GROUP_ID => $relations]);

        return $keyRelationResolver;
    }

    /**
     * @param array<int, KeyConfig> $keyConfigs
     *
     * @throws Exception
     */
    private function createKeyGroupLayoutService(
        array $keyConfigs,
        KeyRelationResolverInterface $keyRelationResolver
    ): KeyGroupLayoutService {
        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        $serviceResolver = new MultiValueServiceResolver();

        return new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $this->createLabelResolver(),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
            new InMemoryKeyLocalizationResolver(),
            new KeyQualifierResolver($serviceResolver),
            new ProvidedOptionNarrower(),
            new NullLogger()
        );
    }

    /**
     * @throws Exception
     */
    private function createGroupService(
        KeyRelationResolverInterface $keyRelationResolver,
        KeyGroupLayoutService $keyGroupLayoutService,
        SubGroupLayoutResolver $subGroupLayoutResolver
    ): GroupService {
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
            new ConcreteObjectResolver(),
            $groupConfigRepository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(GroupHydratorInterface::class),
            $keyRelationResolver,
            $keyGroupLayoutService,
            new GroupLayoutHydrator(),
            $this->createMock(ClassDefinitionResolverInterface::class),
            $subGroupLayoutResolver,
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );
    }

    /**
     * The payload core builds for an already assigned group: the group, its keys and their
     * definitions, and nothing of what the two paths are held against each other on here.
     *
     * @param KeyGroupRelation[] $relations
     */
    private function createFieldDefinition(array $relations): Classificationstore
    {
        $keys = [];

        foreach ($relations as $keyId => $relation) {
            $definition = new Input();
            $definition->setName($relation->getName());

            $keys[] = [
                'name' => $relation->getName(),
                'id' => $keyId,
                'description' => $relation->getDescription(),
                'localized' => null,
                'definition' => $definition,
            ];
        }

        $fieldDefinition = new Classificationstore();
        $fieldDefinition->setName(self::FIELD_NAME);
        $fieldDefinition->setStoreId(self::STORE_ID);
        $fieldDefinition->activeGroupDefinitions = [
            self::GROUP_ID => [
                'name' => self::GROUP_NAME,
                'id' => self::GROUP_ID,
                'description' => 'Technical attributes',
                'keys' => $keys,
            ],
        ];

        return $fieldDefinition;
    }

    /**
     * The enricher reads the key configuration off the definition cache the layout has already
     * filled; the layout service beside it resolves the same one through KeyConfigResolver.
     *
     * @param array<int, KeyConfig> $keyConfigs
     *
     * @throws Exception
     */
    private function createDefinitionCacheResolver(array $keyConfigs): DefinitionCacheResolverInterface
    {
        $definitionCacheResolver = $this->createMock(DefinitionCacheResolverInterface::class);
        $definitionCacheResolver
            ->method('get')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        return $definitionCacheResolver;
    }

    /**
     * Only SUB_GROUP_A has a row: a dangling identifier is left out by both paths alike.
     *
     * @throws Exception
     */
    private function createSubGroupRepository(): SubGroupRepositoryInterface
    {
        $config = new SubGroupConfig();
        $config->setIdentifier(self::SUB_GROUP_A);
        $config->setName('Dimensions');
        $config->setDescription('Dimensions of the product');

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository
            ->method('getByIdentifiers')
            ->with(self::STORE_ID, $this->anything())
            ->willReturnCallback(
                static fn (int $storeId, array $identifiers): array => array_intersect_key(
                    [self::SUB_GROUP_A => $config],
                    array_flip($identifiers)
                )
            );

        return $subGroupRepository;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function membersOfLayout(GroupLayout $groupLayout): array
    {
        return array_map(
            static fn (KeyLayout $key): array => [
                'subGroup' => $key->getSubGroup(),
                'variants' => $key->getVariants(),
                'multiValue' => $key->getMultiValue(),
                'maximum' => $key->getMaximum(),
                'defaultVariant' => $key->getDefaultVariant(),
                'defaultVariantLabel' => $key->getDefaultVariantLabel(),
            ],
            $groupLayout->getKeys()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function membersOfPayload(Classificationstore $fieldDefinition): array
    {
        return array_map(
            static fn (array $key): array => array_intersect_key($key, array_flip(self::KEY_MEMBERS)),
            $this->enrichedGroup($fieldDefinition)['keys']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichedGroup(Classificationstore $fieldDefinition): array
    {
        return $fieldDefinition->activeGroupDefinitions[self::GROUP_ID];
    }
}
