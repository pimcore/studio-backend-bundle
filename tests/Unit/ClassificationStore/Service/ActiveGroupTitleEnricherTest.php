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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\VariantLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifiers;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\LabelLanguageResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SubGroupLayoutResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function array_column;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_map;

/**
 * Guards the slots this bundle writes into the activeGroupDefinitions payload core owns: a change to
 * the literal at Classificationstore::setActiveGroupDefinitions() empties them, and these assertions
 * fail instead of the editor silently rendering blank labels.
 *
 * @internal
 */
final class ActiveGroupTitleEnricherTest extends Unit
{
    use LabelResolverMockTrait;

    private const int GROUP_ID = 3;

    private const int SECOND_GROUP_ID = 4;

    private const string GROUP_NAME = 'Technical';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const int KEY_ID = 11;

    private const string KEY_NAME = 'weight';

    private const array KEY_NAMES = [11 => self::KEY_NAME, 12 => 'height', 13 => 'depth', 21 => 'claim'];

    private const array LABELS = [11 => 'Weight', 12 => 'Height', 13 => 'Depth', 21 => 'Claim'];

    private const array EXPECTED_KEY_TITLES = ['Weight', 'Height', 'Depth'];

    private const string COLUMN_TITLE = 'Column title';

    private const string DEFINITION_TITLE = 'Definition title';

    private const int STORE_ID = 7;

    private const string SUB_GROUP_A = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SUB_GROUP_B = '01JBQ8X9Z0ABCDEFGHJKMNPQRT';

    private const string NAME_DIMENSIONS = 'Dimensions';

    private const string NAME_APPEARANCE = 'Appearance';

    /** The members a key publishes about its qualified values, beside its sub group. */
    private const array QUALIFIER_MEMBERS = [
        'variants',
        'defaultVariant',
        'defaultVariantLabel',
    ];

    public function testEveryKeyOfAnActiveGroupCarriesATitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher()->enrich($fieldDefinition);

        $this->assertSame(
            self::EXPECTED_KEY_TITLES,
            $this->keyTitles($fieldDefinition, self::GROUP_ID)
        );
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testEveryActiveGroupCarriesItsNameAsTitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher()->enrich($fieldDefinition);

        $this->assertSame(self::GROUP_NAME, $this->groupTitle($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * Core reindexes activeGroupDefinitions with usort() whenever a collection mapping exists, so the
     * payload is group-id-keyed sometimes and zero-indexed other times.
     */
    public function testAZeroIndexedPayloadIsEnrichedTheSameWay(): void
    {
        $fieldDefinition = $this->createFieldDefinition([$this->createGroup()]);

        $this->createEnricher()->enrich($fieldDefinition);

        $this->assertSame(self::GROUP_NAME, $this->groupTitle($fieldDefinition, 0));
        $this->assertSame(self::EXPECTED_KEY_TITLES, $this->keyTitles($fieldDefinition, 0));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAClassificationStoreNestedInTheLayoutIsReached(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher()->enrich($this->nestInALayout($fieldDefinition));

        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * The whole point of the resolver: a group costs one label query, not one per key. Every group of
     * every classification store field of the layout gets its own, and no title of a group is resolved
     * before the preload that covers it.
     */
    public function testEveryGroupIsPreloadedOnceBeforeAnyOfItsTitlesIsResolved(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroup(),
            self::SECOND_GROUP_ID => $this->createGroupWithKeys([$this->createKey(21)]),
        ]);
        $nested = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $root = new Panel();
        $root->setChildren([$this->nestInALayout($fieldDefinition), $nested]);

        $this->createEnricher()->enrich($root);

        $this->assertSame([[11, 12, 13], [21], [self::KEY_ID]], $this->preloadedIds());
        $this->assertSame(
            [
                'preload', 'getTitle', 'getTitle', 'getTitle',
                'preload', 'getTitle',
                'preload', 'getTitle',
            ],
            $this->calledMethods()
        );
    }

    /**
     * The label the resolver answers with wins over the title carried by the serialized field
     * definition, which the enricher does not read at all any more.
     */
    public function testTheResolvedLabelIsPublishedInsteadOfTheDefinitionTitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID, self::DEFINITION_TITLE),
            ]),
        ]);

        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($fieldDefinition);

        $this->assertSame([self::COLUMN_TITLE], $this->keyTitles($fieldDefinition, self::GROUP_ID));
    }

    public function testChangingOnlyTheDefinitionTitleChangesNothing(): void
    {
        $withTitle = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID, self::DEFINITION_TITLE),
            ]),
        ]);
        $withoutTitle = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($withTitle);
        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($withoutTitle);

        $this->assertSame(
            $this->keyTitles($withoutTitle, self::GROUP_ID),
            $this->keyTitles($withTitle, self::GROUP_ID)
        );
    }

    /**
     * A resolved '0' is a configured label like any other: only an unresolved one falls back to the
     * key name.
     */
    public function testAKeyTitleOfZeroIsCarriedOver(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->createEnricher([self::KEY_ID => '0'])->enrich($fieldDefinition);

        $this->assertSame(['0'], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAKeyWithoutAnIdFallsBackToItsName(): void
    {
        $key = $this->createKey(self::KEY_ID);
        unset($key['id']);

        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$key]),
        ]);

        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($fieldDefinition);

        $this->assertSame([self::KEY_NAME], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAKeyWhoseConfigurationCannotBeLoadedFallsBackToItsName(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $enricher = $this->newEnricher(
            $this->createLabelResolver([self::KEY_ID => [self::LANGUAGE => self::COLUMN_TITLE]]),
            $this->createDefinitionCacheResolver([]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame([self::KEY_NAME], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * The label row of the requesting user's language is what the editor sees, ahead of the title
     * column the chain ends on.
     */
    public function testTheLabelOfTheRequestingUsersLanguageIsPublished(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID, self::DEFINITION_TITLE),
            ]),
        ]);

        $enricher = $this->newEnricher(
            $this->createLabelResolver([self::KEY_ID => [self::LANGUAGE => 'Gewicht']]),
            $this->createDefinitionCacheResolver([
                $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            ]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame(['Gewicht'], $this->keyTitles($fieldDefinition, self::GROUP_ID));
    }

    /**
     * An installation with an empty labels table is unchanged from the release before: the key's
     * title column, and then its technical name.
     */
    public function testWithoutALabelRowTheTitleColumnAndThenTheNameIsPublished(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID),
                $this->createKey(12),
            ]),
        ]);

        $enricher = $this->newEnricher(
            $this->createLabelResolver(),
            $this->createDefinitionCacheResolver([
                $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
                $this->createKeyConfig(12, self::KEY_NAMES[12], ''),
            ]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame(
            [self::COLUMN_TITLE, self::KEY_NAMES[12]],
            $this->keyTitles($fieldDefinition, self::GROUP_ID)
        );
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * A layout read without a resolvable user is labelled in the installation default language
     * rather than left unlabelled.
     */
    public function testWithoutACurrentUserTheDefaultLanguageIsUsed(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $enricher = $this->newEnricher(
            $this->createLabelResolver([self::KEY_ID => [self::DEFAULT_LANGUAGE => self::COLUMN_TITLE]]),
            $this->createDefinitionCacheResolver([$this->createKeyConfig(self::KEY_ID, self::KEY_NAME)]),
            $this->createLabelLanguageResolver(null, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame([self::COLUMN_TITLE], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertSame(
            [self::DEFAULT_LANGUAGE, self::DEFAULT_LANGUAGE],
            array_column($this->labelResolverCalls, 'language')
        );
    }

    /**
     * The three members a key declares about its qualified values are published beside the title, off
     * the key configuration the layout has already loaded, so an already assigned group renders the
     * variant editors the group layout endpoint renders for a freshly added one.
     */
    public function testEveryKeyCarriesWhatItsConfigurationDeclaresAboutItsQualifiedValues(): void
    {
        $qualifiers = new KeyQualifiers(
            variants: [new VariantLayout('metric', 'Metric'), new VariantLayout('imperial')],
            defaultVariant: false,
            defaultVariantLabel: 'Standard',
        );

        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->createEnricher(self::LABELS, null, $this->createKeyGroupLayoutService($qualifiers))
            ->enrich($fieldDefinition);

        $key = $this->keys($fieldDefinition, self::GROUP_ID)[0];

        $this->assertEquals($qualifiers->getVariants(), $key['variants']);
        $this->assertArrayNotHasKey('multiValue', $key);
        $this->assertArrayNotHasKey('maximum', $key);
        $this->assertFalse($key['defaultVariant']);
        $this->assertSame('Standard', $key['defaultVariantLabel']);
    }

    /**
     * A key whose configuration the layout never cached reports null on all three, rather than the
     * empty list and false a client would read as a key that permits no variant.
     */
    public function testAKeyWithoutAConfigurationReportsNullOnEveryQualifierMember(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->newEnricher(
            $this->createLabelResolver(),
            $this->createDefinitionCacheResolver([]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        )->enrich($fieldDefinition);

        $key = $this->keys($fieldDefinition, self::GROUP_ID)[0];

        foreach (self::QUALIFIER_MEMBERS as $member) {
            $this->assertArrayHasKey($member, $key);
            $this->assertNull($key[$member]);
        }
    }

    /**
     * The sub group a key sits in belongs to the assignment rather than to the key, so it is read off
     * the relation; the payload core builds carries no trace of it.
     */
    public function testEveryKeyCarriesTheSubGroupOfItsRelation(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID),
                $this->createKey(12),
            ]),
        ]);

        $this->createEnricherWithRelations([
            self::KEY_ID => $this->createRelation(self::KEY_ID, self::SUB_GROUP_A),
            12 => $this->createRelation(12),
        ])->enrich($fieldDefinition);

        $this->assertSame([self::SUB_GROUP_A, null], $this->keySubGroups($fieldDefinition));
    }

    /**
     * A key the group no longer relates to keeps every other slot and reports no sub group, instead
     * of taking the one of whichever relation sits in its position.
     */
    public function testAKeyWithoutARelationReportsNoSubGroup(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->createEnricherWithRelations([12 => $this->createRelation(12, self::SUB_GROUP_A)])
            ->enrich($fieldDefinition);

        $this->assertSame([null], $this->keySubGroups($fieldDefinition));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * The group carries the sub groups its relations name, resolved from the vocabulary of the store
     * the field is bound to and ordered by the first key carrying each, which is the order the editor
     * renders its sections in.
     */
    public function testTheGroupCarriesTheSubGroupsOfItsRelations(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        // the relations arrive in sorter then id order, and B's first key comes before A's
        $this->createEnricherWithRelations([
            13 => $this->createRelation(13, self::SUB_GROUP_B),
            11 => $this->createRelation(11, self::SUB_GROUP_A),
            12 => $this->createRelation(12),
        ])->enrich($fieldDefinition);

        $this->assertSame(
            [self::NAME_DIMENSIONS, self::NAME_APPEARANCE],
            array_map(
                static fn (SubGroupLayout $subGroup): string => $subGroup->getName(),
                $this->subGroups($fieldDefinition)
            )
        );
    }

    public function testAGroupWhoseRelationsNameNoSubGroupCarriesAnEmptyList(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricherWithRelations([11 => $this->createRelation(11)])->enrich($fieldDefinition);

        $this->assertSame([], $this->subGroups($fieldDefinition));
    }

    /**
     * The relations of every active group are read with one statement, the way the labels are: a
     * layout with several active groups costs one relation query, not one per group.
     */
    public function testTheRelationsOfEveryGroupAreReadWithOneStatement(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroup(),
            self::SECOND_GROUP_ID => $this->createGroupWithKeys(
                [$this->createKey(21)],
                self::SECOND_GROUP_ID
            ),
        ]);

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->expects($this->once())
            ->method('getByGroups')
            ->with([self::GROUP_ID, self::SECOND_GROUP_ID])
            ->willReturn([]);
        $keyRelationResolver
            ->expects($this->never())
            ->method('getByGroup');

        $this->createEnricher(self::LABELS, $keyRelationResolver)->enrich($fieldDefinition);

        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * A field definition with no active group reads nothing at all: neither the relations nor the
     * store of a layout that renders no classification store group.
     */
    public function testAFieldWithoutActiveGroupsReadsNoRelations(): void
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->expects($this->never())
            ->method('getByGroups');

        $this->createEnricher(self::LABELS, $keyRelationResolver)->enrich($this->createFieldDefinition([]));
    }

    /**
     * @param array<int, string> $labels key id => label stored for the user's language
     */
    private function createEnricher(
        array $labels = self::LABELS,
        ?KeyRelationResolverInterface $keyRelationResolver = null,
        ?KeyGroupLayoutServiceInterface $keyGroupLayoutService = null,
        ?SubGroupRepositoryInterface $subGroupRepository = null,
    ): ActiveGroupTitleEnricher {
        $labelsByKeyId = [];
        $keyConfigs = [];

        foreach (self::KEY_NAMES as $keyId => $keyName) {
            $keyConfigs[] = $this->createKeyConfig($keyId, $keyName);

            if (isset($labels[$keyId])) {
                $labelsByKeyId[$keyId] = [self::LANGUAGE => $labels[$keyId]];
            }
        }

        return $this->newEnricher(
            $this->createLabelResolver($labelsByKeyId),
            $this->createDefinitionCacheResolver($keyConfigs),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
            $keyRelationResolver,
            $keyGroupLayoutService,
            $subGroupRepository
        );
    }

    /**
     * An enricher whose group answers with the given relations, over the vocabulary of the store the
     * field definition is bound to.
     *
     * @param KeyGroupRelation[] $relations keyed by key id, in relation order
     */
    private function createEnricherWithRelations(array $relations): ActiveGroupTitleEnricher
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroups')
            ->willReturn([self::GROUP_ID => $relations]);

        return $this->createEnricher(
            self::LABELS,
            $keyRelationResolver,
            null,
            $this->createSubGroupRepository()
        );
    }

    private function newEnricher(
        LabelResolverInterface $labelResolver,
        DefinitionCacheResolverInterface $definitionCacheResolver,
        LabelLanguageResolverInterface $labelLanguageResolver,
        ?KeyRelationResolverInterface $keyRelationResolver = null,
        ?KeyGroupLayoutServiceInterface $keyGroupLayoutService = null,
        ?SubGroupRepositoryInterface $subGroupRepository = null,
    ): ActiveGroupTitleEnricher {
        return new ActiveGroupTitleEnricher(
            $labelResolver,
            $definitionCacheResolver,
            $labelLanguageResolver,
            $keyRelationResolver ?? $this->createMock(KeyRelationResolverInterface::class),
            $keyGroupLayoutService ?? $this->createKeyGroupLayoutService(),
            new SubGroupLayoutResolver(
                $subGroupRepository ?? $this->createMock(SubGroupRepositoryInterface::class)
            )
        );
    }

    /**
     * KeyQualifiers is final, so the qualifiers a key reports are stubbed rather than auto-doubled.
     */
    private function createKeyGroupLayoutService(?KeyQualifiers $qualifiers = null): KeyGroupLayoutServiceInterface
    {
        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getKeyQualifiers')
            ->willReturn($qualifiers ?? new KeyQualifiers(null, null, null));

        return $keyGroupLayoutService;
    }

    /**
     * The vocabulary is read for the store the field definition names, never for another.
     */
    private function createSubGroupRepository(): SubGroupRepositoryInterface
    {
        $vocabulary = [
            self::SUB_GROUP_A => $this->createSubGroupConfig(self::SUB_GROUP_A, self::NAME_APPEARANCE),
            self::SUB_GROUP_B => $this->createSubGroupConfig(self::SUB_GROUP_B, self::NAME_DIMENSIONS),
        ];

        $subGroupRepository = $this->createMock(SubGroupRepositoryInterface::class);
        $subGroupRepository
            ->method('getByIdentifiers')
            ->with(self::STORE_ID, $this->anything())
            ->willReturnCallback(
                static fn (int $storeId, array $identifiers): array => array_intersect_key(
                    $vocabulary,
                    array_flip($identifiers)
                )
            );

        return $subGroupRepository;
    }

    private function createSubGroupConfig(string $identifier, string $name): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setIdentifier($identifier);
        $config->setName($name);
        $config->setDescription('Description of ' . $name);

        return $config;
    }

    private function createRelation(int $keyId, ?string $subGroup = null): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(self::KEY_NAMES[$keyId]);
        $relation->setSubGroup($subGroup);

        return $relation;
    }

    /**
     * @param list<KeyConfig> $keyConfigs
     */
    private function createDefinitionCacheResolver(array $keyConfigs): DefinitionCacheResolverInterface
    {
        $byId = [];
        foreach ($keyConfigs as $keyConfig) {
            $byId[$keyConfig->getId() ?? 0] = $keyConfig;
        }

        $definitionCacheResolver = $this->createMock(DefinitionCacheResolverInterface::class);
        $definitionCacheResolver
            ->method('get')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $byId[$keyId] ?? null);

        return $definitionCacheResolver;
    }

    private function nestInALayout(Classificationstore $fieldDefinition): Panel
    {
        $localizedFields = new Localizedfields();
        $localizedFields->setChildren([$fieldDefinition]);

        $inner = new Panel();
        $inner->setChildren([new Input(), $localizedFields]);

        $root = new Panel();
        $root->setChildren([$inner]);

        return $root;
    }

    /**
     * @param array<int, array<string, mixed>> $activeGroupDefinitions
     */
    private function createFieldDefinition(array $activeGroupDefinitions): Classificationstore
    {
        $fieldDefinition = new Classificationstore();
        $fieldDefinition->setName('classificationStore');
        $fieldDefinition->setStoreId(self::STORE_ID);
        $fieldDefinition->activeGroupDefinitions = $activeGroupDefinitions;

        return $fieldDefinition;
    }

    /**
     * @return array<string, mixed>
     */
    private function createGroup(): array
    {
        return $this->createGroupWithKeys([
            $this->createKey(self::KEY_ID),
            $this->createKey(12),
            $this->createKey(13),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $keys
     *
     * @return array<string, mixed>
     */
    private function createGroupWithKeys(array $keys, int $groupId = self::GROUP_ID): array
    {
        return [
            'name' => self::GROUP_NAME,
            'id' => $groupId,
            'description' => 'Technical attributes',
            'keys' => $keys,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createKey(int $keyId, ?string $definitionTitle = null): array
    {
        $definition = new Input();
        $definition->setName(self::KEY_NAMES[$keyId]);

        if ($definitionTitle !== null) {
            $definition->setTitle($definitionTitle);
        }

        return [
            'name' => self::KEY_NAMES[$keyId],
            'id' => $keyId,
            'description' => '',
            'definition' => $definition,
        ];
    }

    private function groupTitle(Classificationstore $fieldDefinition, int|string $groupIndex): mixed
    {
        return $fieldDefinition->activeGroupDefinitions[$groupIndex]['title'] ?? null;
    }

    /**
     * @return array<int, mixed>
     */
    private function keyTitles(Classificationstore $fieldDefinition, int|string $groupIndex): array
    {
        return array_column($this->keys($fieldDefinition, $groupIndex), 'title');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function keys(Classificationstore $fieldDefinition, int|string $groupIndex): array
    {
        return $fieldDefinition->activeGroupDefinitions[$groupIndex]['keys'];
    }

    /**
     * @return array<int, ?string> the sub group identifier of every key of the first group
     */
    private function keySubGroups(Classificationstore $fieldDefinition): array
    {
        return array_map(
            static fn (array $key): ?string => $key['subGroup'],
            $this->keys($fieldDefinition, self::GROUP_ID)
        );
    }

    /**
     * @return SubGroupLayout[]
     */
    private function subGroups(Classificationstore $fieldDefinition): array
    {
        return $fieldDefinition->activeGroupDefinitions[self::GROUP_ID]['subGroups'];
    }

    private function assertNoSlotIsEmpty(Classificationstore $fieldDefinition): void
    {
        foreach (array_keys($fieldDefinition->activeGroupDefinitions) as $groupIndex) {
            $titles = [
                $this->groupTitle($fieldDefinition, $groupIndex),
                ...$this->keyTitles($fieldDefinition, $groupIndex),
            ];

            foreach ($titles as $title) {
                $this->assertNotNull($title);
                $this->assertNotSame('', $title);
            }
        }
    }
}
