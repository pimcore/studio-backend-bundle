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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\SubGroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\VariantLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
final class GroupServiceTest extends Unit
{
    use LabelResolverMockTrait;

    private const int GROUP_ID = 3;

    private const int OBJECT_ID = 5;

    private const string FIELD_NAME = 'classificationStore';

    private const string GROUP_NAME = 'Technical';

    private const string SUB_GROUP_A = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SUB_GROUP_B = '01JBQ8X9Z0ABCDEFGHJKMNPQRT';

    private const string NAME_DIMENSIONS = 'Dimensions';

    private const string NAME_APPEARANCE = 'Appearance';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    /**
     * The largest group ETIM ships, which is the size the per-key label query used to cost 121 queries
     * on.
     */
    private const int ETIM_MAX_KEYS = 121;

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
            ->willReturnCallback(static fn (int $keyId, string $language): ?string => $titles[$keyId]);

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertCount(3, $keys);
        $this->assertSame('Weight', $keys[0]->getTitle());
        $this->assertNull($keys[1]->getTitle());
        $this->assertSame('', $keys[2]->getTitle());
    }

    /**
     * Every key of the group carries its own `localized` flag, passed through as the tri-state it
     * is: the one key that states nothing reports null rather than the false the other two would
     * make it look like.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheLocalizationFlagOfEveryKey(): void
    {
        $localized = [11 => true, 12 => false, 13 => null];

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
            ->method('getKeyLocalized')
            ->willReturnCallback(static fn (int $keyId): ?bool => $localized[$keyId]);

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertTrue($keys[0]->getLocalized());
        $this->assertFalse($keys[1]->getLocalized());
        $this->assertNull($keys[2]->getLocalized());
    }

    /**
     * Every key of the group carries what it declares about its qualified values, read off its own
     * key configuration: a key of type `multiValue` publishes its variants as objects in the order it
     * declares them, its `multiValue` setting and its `maximum`. A key of any other type reports null
     * for all three - whatever its blob carries - rather than an empty list and false, which a client
     * would read as a key that permits no variant and holds one value.
     *
     * The layout-by-key path has the same assertion in
     * {@see KeyGroupLayoutServiceTest::testTheKeyLayoutCarriesWhatTheKeyDeclaresAboutItsValues()};
     * both ask the same two methods of {@see KeyGroupLayoutServiceInterface}, which is the single
     * place either answer comes from.
     *
     * @throws Exception
     */
    public function testEveryKeyCarriesWhatItDeclaresAboutItsQualifiedValues(): void
    {
        $keys = $this
            ->createServiceWithRealKeyConfigs([
                11 => [
                    'fieldtype' => 'multiValue',
                    'variants' => [['name' => 'metric', 'label' => 'Metric'], 'imperial'],
                    'multiValue' => true,
                    'maximum' => 3,
                    'unqualifiedValue' => false,
                    'unqualifiedValueLabel' => 'Standard',
                ],
                12 => ['fieldtype' => 'quantityValue', 'variants' => ['metric'], 'multiValue' => true],
                13 => ['fieldtype' => 'input'],
            ])
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        // the same objects and the same maximum the layout-by-key path publishes for that key
        $this->assertEquals([new VariantLayout('metric', 'Metric'), new VariantLayout('imperial')], $keys[0]->getVariants());
        $this->assertTrue($keys[0]->getMultiValue());
        $this->assertSame(3, $keys[0]->getMaximum());
        $this->assertFalse($keys[0]->getDefaultVariant());
        $this->assertSame('Standard', $keys[0]->getDefaultVariantLabel());

        $this->assertNull($keys[1]->getDefaultVariant());
        $this->assertNull($keys[1]->getDefaultVariantLabel());
        $this->assertNull($keys[1]->getVariants());
        $this->assertNull($keys[1]->getMultiValue());
        $this->assertNull($keys[1]->getMaximum());

        $this->assertNull($keys[2]->getVariants());
        $this->assertNull($keys[2]->getMultiValue());
        $this->assertNull($keys[2]->getMaximum());
    }

    /**
     * A group whose keys declare nothing is served exactly as before.
     *
     * @throws Exception
     */
    public function testAGroupOfKeysDeclaringNothingReportsNullOnBothMembers(): void
    {
        $keys = $this
            ->createServiceWithRealKeyLabels([1 => [self::LANGUAGE => 'Gewicht']])
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertNull($keys[0]->getVariants());
        $this->assertNull($keys[0]->getMultiValue());
        $this->assertNull($keys[0]->getMaximum());
    }

    /**
     * @param array<int, array<string, mixed>> $definitions one entry per key of the group
     *
     * @throws Exception
     */
    private function createServiceWithRealKeyConfigs(array $definitions): GroupService
    {
        $relations = [];
        $keyConfigs = [];
        foreach ($definitions as $keyId => $definition) {
            $keyName = sprintf('key%d', $keyId);
            $relations[$keyId] = $this->createRelation($keyId, $keyName);
            $keyConfig = $this->createKeyConfig($keyId, $keyName);
            $keyConfig->setType($definition['fieldtype'] ?? 'input');
            $keyConfig->setDefinition((string) json_encode($definition));
            $keyConfigs[$keyId] = $keyConfig;
        }

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturnCallback(static function (array $definition, string $type): Input|MultiValue {
                if ($type !== 'multiValue') {
                    return new Input();
                }

                $multiValue = DeclaredMultiValue::from($definition);
                $multiValue->setVariants($definition['variants'] ?? []);
                $multiValue->setMultiValue($definition['multiValue'] ?? false);
                $multiValue->setMaximum($definition['maximum'] ?? null);

                return $multiValue;
            });

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        $keyGroupLayoutService = new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $this->createLabelResolver([]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
            new InMemoryKeyLocalizationResolver(),
            new KeyQualifierResolver($serviceResolver)
        );

        return $this->createService($keyRelationResolver, $keyGroupLayoutService);
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
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID));

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
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertSame([13, 11, 12], array_map(static fn (KeyLayout $key): int => $key->getId(), $keys));
    }

    /**
     * The group's whole label set is read with one statement, so the largest group ETIM ships costs
     * one label query instead of 121.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionResolvesEveryKeyLabelAfterOnePreload(): void
    {
        $labels = [];
        for ($keyId = 1; $keyId <= self::ETIM_MAX_KEYS; $keyId++) {
            $labels[$keyId] = [self::LANGUAGE => sprintf('Label %d', $keyId)];
        }

        $keys = $this
            ->createServiceWithRealKeyLabels($labels)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertSame([array_keys($labels)], $this->preloadedIds());
        $this->assertCount(self::ETIM_MAX_KEYS, $keys);
        $this->assertSame(
            array_map(static fn (int $keyId): string => sprintf('Label %d', $keyId), array_keys($labels)),
            array_map(static fn (KeyLayout $key): ?string => $key->getTitle(), $keys)
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionPublishesTheLabelOfTheRequestingUsersLanguage(): void
    {
        $keys = $this
            ->createServiceWithRealKeyLabels([1 => [self::LANGUAGE => 'Gewicht']])
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertSame('Gewicht', $keys[0]->getTitle());
    }

    /**
     * @param array<int, array<string, string>> $labels one entry per key of the group
     *
     * @throws Exception
     */
    private function createServiceWithRealKeyLabels(array $labels): GroupService
    {
        $relations = [];
        $keyConfigs = [];
        foreach (array_keys($labels) as $keyId) {
            $keyName = sprintf('key%d', $keyId);
            $relations[$keyId] = $this->createRelation($keyId, $keyName);
            $keyConfigs[$keyId] = $this->createKeyConfig($keyId, $keyName);
        }

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturnCallback(static function (array $definition, string $type): Input|MultiValue {
                if ($type !== 'multiValue') {
                    return new Input();
                }

                $multiValue = DeclaredMultiValue::from($definition);
                $multiValue->setVariants($definition['variants'] ?? []);
                $multiValue->setMultiValue($definition['multiValue'] ?? false);
                $multiValue->setMaximum($definition['maximum'] ?? null);

                return $multiValue;
            });

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        $keyGroupLayoutService = new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $this->createLabelResolver($labels),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
            new InMemoryKeyLocalizationResolver(),
            new KeyQualifierResolver($serviceResolver)
        );

        return $this->createService($keyRelationResolver, $keyGroupLayoutService);
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
     * An objectId that does not resolve is still a 404 — only its absence is tolerated. Zero is one
     * of those: `LayoutParameter` accepts it, so it reaches the lookup as a supplied id and must not
     * be read as an omission the way a truthiness check would read it.
     *
     * @throws Exception
     */
    public function testAnObjectIdThatDoesNotResolveIsStillNotFound(): void
    {
        foreach ([999999, 0] as $objectId) {
            $concreteObjectResolver = $this->createMock(ConcreteObjectResolverInterface::class);
            $concreteObjectResolver
                ->expects($this->once())
                ->method('getById')
                ->with($objectId)
                ->willReturn(null);

            $service = $this->createService(
                $this->createMock(KeyRelationResolverInterface::class),
                $this->createMock(KeyGroupLayoutServiceInterface::class),
                $concreteObjectResolver
            );

            try {
                $service->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', $objectId));
                $this->fail('Expected the object ID ' . $objectId . ' to be refused.');
            } catch (NotFoundException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
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
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
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
