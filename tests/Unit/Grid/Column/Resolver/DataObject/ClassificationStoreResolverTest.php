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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\DataObject;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\EngineCapabilityGuard;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueParser;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ClassificationStoreResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\MultiValueServiceResolver;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use function array_filter;
use function array_key_exists;
use function json_encode;

/**
 * @internal
 */
final class ClassificationStoreResolverTest extends Unit
{
    private const int GROUP_ID = 10;

    private const int KEY_ID = 400;

    private const string COLUMN_KEY = 'cs';

    private const string COLUMN_TYPE = 'dataobject.classificationstore';

    private const string KEY_CONFIG_CACHE_KEY = 'cs_keyconfig_' . self::KEY_ID;

    private const string INPUT_TYPE = 'input';

    private const string GROUP_IDENTIFIER = 'product-data';

    private const string KEY_IDENTIFIER = 'weight';

    /**
     * @var string[]
     */
    private array $primedKeyConfigCacheKeys = [];

    protected function _after(): void
    {
        RuntimeCache::getInstance()->offsetUnset(self::KEY_CONFIG_CACHE_KEY);

        foreach ($this->primedKeyConfigCacheKeys as $cacheKey) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }

        $this->primedKeyConfigCacheKeys = [];
    }

    /**
     * A stale saved grid configuration can still contain the bare classification store field column.
     * It must load as an empty cell instead of failing the whole grid request.
     */
    public function testResolveForCoreElementReturnsEmptyDataForBareColumn(): void
    {
        $result = $this->createResolver()->resolveForCoreElement(
            $this->createBareColumn(),
            $this->makeEmpty(Concrete::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame(self::COLUMN_KEY, $result->getKey());
        $this->assertSame(self::COLUMN_TYPE, $result->getFieldType());
    }

    public function testResolveForExportReturnsEmptyDataForBareColumn(): void
    {
        $result = $this->createResolver()->resolveForExport(
            $this->createBareColumn(),
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame(self::COLUMN_KEY, $result->getKey());
        $this->assertSame(self::COLUMN_TYPE, $result->getFieldType());
    }

    /**
     * One grid cell holds one value, so a `multiValue` key serving a list of qualified ones shows its
     * default slot rather than being handed the whole list - the cell is typed on the key's own field
     * type, which cannot render a list of entry objects.
     *
     * @dataProvider cellValueProvider
     *
     * @param array<string, mixed> $declaration
     *
     * @throws Exception
     */
    public function testACellShowsTheDefaultSlotOfTheKeysSlot(
        string $type,
        array $declaration,
        mixed $normalized,
        mixed $expected
    ): void {
        $this->primeKeyConfig($declaration, $type);

        $result = $this->createResolver($normalized)->resolveForCoreElement(
            $this->createKeyColumn(),
            $this->makeEmpty(Concrete::class, ['getId' => 1])
        );

        $this->assertSame($expected, $result->getValue());
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: mixed, 3: mixed}>
     */
    public static function cellValueProvider(): array
    {
        $multiValue = MultiValueServiceResolver::MULTI_VALUE_TYPE;
        $declaring = ['variants' => ['metric', 'imperial'], 'multiValue' => true];
        $plain = ['fieldtype' => 'input'];

        return [
            'a list of qualified entries' => [
                $multiValue,
                $declaring,
                [['v' => '176', 'var' => 'imperial'], ['v' => '80']],
                [['v' => '80', 'p' => 0]],
            ],
            'an empty qualified slot' => [$multiValue, $declaring, null, null],
            // every key that is not of type `multiValue` keeps reaching the cell untouched
            'a single value' => [self::INPUT_TYPE, $plain, '80', '80'],
            'a multi select value' => [self::INPUT_TYPE, $plain, ['red', 'green'], ['red', 'green']],
            'the string zero' => [self::INPUT_TYPE, $plain, '0', '0'],
            'an empty slot' => [self::INPUT_TYPE, $plain, null, null],
            'a blob naming variants on another type' => [
                self::INPUT_TYPE,
                $declaring,
                [['v' => '176', 'var' => 'imperial'], ['v' => '80']],
                [['v' => '176', 'var' => 'imperial'], ['v' => '80']],
            ],
        ];
    }

    /**
     * The cell and the export of one `multiValue` default column show the same value: the default variant
     * at position 0, and nothing where the key holds nothing or holds named variants alone. The cell is handed what the object data endpoint published for the row, and the
     * export is handed the one value it injects for the same store.
     *
     * @dataProvider slotHoldingEntriesProvider
     *
     * @param array<string, array<int, mixed>> $storedVariants the values core holds, by variant
     *
     * @throws Exception
     */
    public function testTheCellAndTheExportOfOneColumnShowTheSameValue(
        mixed $published,
        array $storedVariants,
        mixed $expected
    ): void {
        $this->primeKeyConfig(
            ['variants' => ['metric', 'imperial'], 'multiValue' => true],
            MultiValueServiceResolver::MULTI_VALUE_TYPE
        );

        $injected = null;
        $reached = false;
        $resolver = $this->createResolver(
            $published,
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );
        $element = $this->createElementHolding($storedVariants['default'][0] ?? null, $storedVariants);

        $cell = $resolver->resolveForCoreElement($this->createKeyColumn(), $element);
        $resolver->resolveForExport($this->createKeyColumn(), $element, $this->makeEmpty(UserInterface::class));

        // a repeating key's cell is the entry list the write path reads, the export formats the same value
        $this->assertSame($expected === null ? null : [['v' => $expected, 'p' => 0]], $cell->getValue());
        $this->assertSame($expected, $injected);
    }

    /**
     * @return array<string, array{0: mixed, 1: array<string, array<int, mixed>>, 2: mixed}>
     */
    public static function slotHoldingEntriesProvider(): array
    {
        return [
            'two variants beside the default one' => [
                [['v' => '80'], ['v' => '176', 'var' => 'imperial']],
                ['default' => [0 => '80'], 'imperial' => [0 => '176']],
                '80',
            ],
            'the default one listed after a variant' => [
                [['v' => '176', 'var' => 'imperial'], ['v' => '80']],
                ['default' => [0 => '80'], 'imperial' => [0 => '176']],
                '80',
            ],
            'no default value' => [
                [['v' => '176', 'var' => 'imperial']],
                ['imperial' => [0 => '176']],
                null,
            ],
            'nothing at all' => [null, [], null],
        ];
    }

    /**
     * The value of a key that declares nothing reaches the cell and the export untouched, however
     * much it looks like a list of entries: the row of a table key whose one column is named `v`
     * holds a list of maps carrying `v`, and the key's declaration - not the shape of that value -
     * decides that this slot holds no entries to reduce.
     *
     * The declaration is asked and the value is not, which is the whole of the fix: while the shape
     * of the value decided, the row of *any* key could be read as an entry list, and this column
     * then showed the first cell of such a table in the grid and injected it into the export of a
     * key that declares no qualifier at all.
     *
     * @throws Exception
     */
    public function testAValueShapedLikeAnEntryListReachesTheCellWhole(): void
    {
        $table = [['v' => '80'], ['v' => '81']];
        $this->primeKeyConfig(['fieldtype' => 'table', 'variants' => ['metric'], 'multiValue' => true]);

        $injected = 'untouched';
        $reached = false;
        $resolver = $this->createResolver(
            $table,
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );
        $element = $this->createElementHolding($table);

        $cell = $resolver->resolveForCoreElement($this->createKeyColumn(), $element);
        $resolver->resolveForExport($this->createKeyColumn(), $element, $this->makeEmpty(UserInterface::class));

        $this->assertSame($table, $cell->getValue());
        // nothing is injected, so the key's own field definition reads the table itself
        $this->assertFalse($reached);
        $this->assertNull($injected);
    }

    /**
     * A key that declares nothing exports exactly as it does today: nothing is injected, so the
     * key's own field definition reads the slot itself, the way it always has.
     *
     * @throws Exception
     */
    public function testAKeyThatDeclaresNothingHasNothingInjectedIntoItsExport(): void
    {
        $this->primeKeyConfig(['fieldtype' => 'input']);

        $injected = null;
        $reached = false;
        $resolver = $this->createResolver(
            '80',
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );

        $resolver->resolveForExport(
            $this->createKeyColumn(),
            $this->createElementHolding('80'),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertFalse($reached);
        $this->assertNull($injected);
    }

    /**
     * The other half of the guard the slot read makes: a saved column naming a field the class no
     * longer carries exports an empty cell, the way it does today, rather than failing the export
     * on a getter that is not there.
     *
     * @throws Exception
     */
    public function testAColumnNamingAFieldTheClassNoLongerHasExportsAnEmptyCell(): void
    {
        $this->primeKeyConfig(['variants' => ['metric'], 'multiValue' => true], MultiValueServiceResolver::MULTI_VALUE_TYPE);

        $injected = 'untouched';
        $reached = false;
        $resolver = $this->createResolver(
            null,
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );

        $result = $resolver->resolveForExport(
            $this->createKeyColumn(),
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(UserInterface::class)
        );

        // nothing is stored at a field the class does not have, so the export is never asked to format
        $this->assertFalse($reached);
        $this->assertSame('untouched', $injected);
        $this->assertSame('', $result->getValue());
    }

    /**
     * The export formats the value with the definition of the assignment, so a unit the assignment overrides
     * is the one the value is formatted with.
     *
     * @throws Exception
     */
    public function testTheExportIsFormattedByTheEffectiveDefinitionOfTheAssignment(): void
    {
        $this->primeKeyConfig(['fieldtype' => 'quantityValue', 'defaultUnit' => 'm']);
        $relation = $this->createRelation();
        $relation->setType('quantityValue');
        $relation->setDefinition('{"fieldtype":"quantityValue","defaultUnit":"m","validUnits":["m","kg"]}');
        $relation->setDefinitionOverride(['defaultUnit' => 'kg']);

        $received = null;
        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getFieldDefinitionFromJson' => static function (array $definition) use (&$received): Data {
                $received = $definition;

                return new Input();
            },
        ]);
        $injected = null;
        $reached = false;
        $resolver = $this->createResolver(
            null,
            $serviceResolver,
            $this->createExportRead($injected, $reached),
            relation: $relation
        );

        $resolver->resolveForExport(
            $this->createKeyColumn(),
            $this->createElementHolding('5'),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame('kg', $received['defaultUnit']);
    }

    /**
     * @dataProvider variantColumnProvider
     *
     * @param array<string, mixed> $config
     * @param array<string, array<int, mixed>> $stored
     *
     * @throws Exception
     */
    public function testAColumnShowsAndExportsTheVariantItAddresses(
        array $config,
        array $stored,
        bool $repeating,
        mixed $expectedCell,
        mixed $expectedExport
    ): void {
        $this->primeKeyConfig(
            ['variants' => ['Metric', 'Imperial'], 'multiValue' => $repeating],
            MultiValueServiceResolver::MULTI_VALUE_TYPE
        );
        $published = [];
        foreach ($stored as $variant => $values) {
            foreach ($values as $position => $value) {
                $published[] = array_filter(
                    ['v' => $value, 'var' => $variant === 'default' ? null : $variant, 'p' => $position ?: null],
                    static fn (mixed $member): bool => $member !== null
                );
            }
        }

        $injected = null;
        $reached = false;
        $resolver = $this->createResolver(
            $published,
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );
        $column = $this->createColumn($config);
        $element = $this->createElementHolding(null, $stored);

        $cell = $resolver->resolveForCoreElement($column, $element);
        $export = $resolver->resolveForExport($column, $element, $this->makeEmpty(UserInterface::class));

        $this->assertSame($expectedCell, $cell->getValue());
        $this->assertSame($expectedExport, $export->getValue());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, array<int, mixed>>, 2: bool, 3: mixed, 4: mixed}>
     */
    public static function variantColumnProvider(): array
    {
        $base = ['groupId' => self::GROUP_ID, 'keyId' => self::KEY_ID];
        $named = ['Metric' => [0 => '50'], 'Imperial' => [0 => '25']];

        return [
            'a column naming a variant reads that slot alone' => [
                [...$base, 'variant' => 'Metric'], $named, false, '50', '50',
            ],
            'the same config without a variant reads the default slot, empty here' => [
                $base, $named, false, null, '',
            ],
            'values under named variants only leave the other variant carrying its own' => [
                [...$base, 'variant' => 'Imperial'], $named, false, '25', '25',
            ],
            'a repeating key exports a bare JSON array and shows its entries as the list the write path reads' => [
                [...$base, 'variant' => 'Metric'], ['Metric' => [0 => '50', 1 => '55']], true, [['v' => '50', 'var' => 'Metric', 'p' => 0], ['v' => '55', 'var' => 'Metric', 'p' => 1]], '["50","55"]',
            ],
        ];
    }

    public function testAColumnConfigNamingAnEmptyVariantIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->validateConfig(['groupId' => 1, 'keyId' => 2, 'variant' => '']);
    }

    public function testAColumnConfigCarriesItsVariant(): void
    {
        $config = $this->createResolver()->validateConfig(['groupId' => 1, 'keyId' => 2, 'variant' => 'Metric']);

        $this->assertSame('Metric', $config->getVariant());
        $this->assertNull($this->createResolver()->validateConfig(['groupId' => 1, 'keyId' => 2])->getVariant());
    }

    public function testValidateConfigStillThrowsForMissingKeyConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->validateConfig([]);
    }

    /**
     * @dataProvider partialKeyConfigProvider
     */
    public function testResolveForCoreElementThrowsForPartialKeyConfig(array $config): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->resolveForCoreElement(
            $this->createColumn($config),
            $this->makeEmpty(Concrete::class)
        );
    }

    public static function partialKeyConfigProvider(): array
    {
        return [
            'only groupId' => [['groupId' => 1]],
            'only keyId' => [['keyId' => 2]],
        ];
    }

    /**
     * Identifiers win over a deliberately wrong integer pair in the same config.
     */
    public function testResolveForCoreElementResolvesFromIdentifiersIgnoringTheIntegerPair(): void
    {
        $result = $this->createResolverWithReference(new ClassificationStoreConfig(5, 6))->resolveForCoreElement(
            $this->createColumn([
                'groupId' => 99,
                'keyId' => 98,
                'groupIdentifier' => self::GROUP_IDENTIFIER,
                'keyIdentifier' => self::KEY_IDENTIFIER,
            ]),
            $this->createObject()
        );

        $this->assertSame('cs.weight', $result->getKey());
        $this->assertSame('42 kg', $result->getValue());
        $this->assertSame(5, $result->getAdditionalAttributes()['groupId']);
        $this->assertSame(6, $result->getAdditionalAttributes()['keyId']);
    }

    /**
     * A config without identifiers resolves from the pair exactly as before.
     */
    public function testResolveForCoreElementResolvesFromTheIntegerPairWithoutIdentifiers(): void
    {
        $result = $this->createResolverWithReference(null)->resolveForCoreElement(
            $this->createColumn(['groupId' => 5, 'keyId' => 6]),
            $this->createObject()
        );

        $this->assertSame('cs.weight', $result->getKey());
        $this->assertSame('42 kg', $result->getValue());
        $this->assertSame('input', $result->getFieldType());
        $this->assertSame(5, $result->getAdditionalAttributes()['groupId']);
        $this->assertSame(6, $result->getAdditionalAttributes()['keyId']);
    }

    /**
     * An identifier-only column is a configured reference: it fails when unresolvable, never renders empty.
     */
    public function testResolveForCoreElementThrowsForAnUnresolvableIdentifierPair(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolverWithUnresolvableReference()->resolveForCoreElement(
            $this->createColumn(['groupId' => 5, 'keyId' => 6, 'groupIdentifier' => 'gone', 'keyIdentifier' => 'gone']),
            $this->createObject()
        );
    }

    public function testResolveForExportThrowsForAnUnresolvableIdentifierPair(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolverWithUnresolvableReference()->resolveForExport(
            $this->createColumn(['groupIdentifier' => 'gone', 'keyIdentifier' => 'gone']),
            $this->createObject(),
            $this->makeEmpty(UserInterface::class)
        );
    }

    public function testIdentifierOnlyColumnIsNotTheEmptyBareColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->resolveForCoreElement(
            $this->createColumn(['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER]),
            $this->makeEmpty(Concrete::class)
        );
    }

    public function testNullIdentifiersAloneStayTheEmptyBareColumn(): void
    {
        $result = $this->createResolver()->resolveForCoreElement(
            $this->createColumn(['groupIdentifier' => null, 'keyIdentifier' => null]),
            $this->makeEmpty(Concrete::class)
        );

        $this->assertNull($result->getValue());
    }

    private function createObject(): Concrete
    {
        return $this->makeEmpty(Concrete::class, ['getClassId' => 'EV']);
    }

    private function createResolverWithUnresolvableReference(): ClassificationStoreResolver
    {
        $reference = $this->makeEmpty(ColumnReferenceServiceInterface::class, [
            'resolve' => static function (): never {
                throw new InvalidArgumentException('does not exist');
            },
        ]);

        return $this->createResolver(reference: $reference);
    }

    private function createResolverWithReference(?ClassificationStoreConfig $resolved): ClassificationStoreResolver
    {
        // the cell now asks the key's own declaration, so the key the reference resolves to is
        // primed the way {@see self::primeKeyConfig()} primes the one the other cells use: this
        // key declares no qualifier, so its value reaches the cell untouched
        $this->primeKeyConfig(['fieldtype' => 'input'], keyId: 6);

        $relation = new KeyGroupRelation();
        $relation->setKeyId(6);
        $relation->setName('weight');
        $relation->setType('input');

        $base = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => new ColumnData(
                'cs',
                null,
                [5 => ['default' => [6 => '42 kg']]],
                'x',
                [5 => ['default' => [6 => new InheritanceData(1, false)]]]
            ),
        ]);

        $keyQualifierResolver = new KeyQualifierResolver(new MultiValueServiceResolver());

        return new ClassificationStoreResolver(
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.adapter' => $base],
            ]),
            $this->makeEmpty(KeyRelationResolverInterface::class, ['getByGroup' => [6 => $relation]]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class),
            $keyQualifierResolver,
            new QualifiedValueSerializer(
                $keyQualifierResolver,
                new QualifiedValueParser($keyQualifierResolver),
                new EngineCapabilityGuard()
            ),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $this->makeEmpty(ColumnReferenceServiceInterface::class, ['resolve' => $resolved]),
            $this->keyConfigResolver(),
        );
    }

    private function keyConfigResolver(): KeyConfigResolverInterface
    {
        // the real model, primed in the runtime cache by primeKeyConfig()
        return $this->makeEmpty(KeyConfigResolverInterface::class, [
            'getById' => static fn (int $id): ?KeyConfig => KeyConfig::getById($id),
        ]);
    }

    private function createBareColumn(): Column
    {
        return $this->createColumn([]);
    }

    private function createKeyColumn(): Column
    {
        return $this->createColumn(['groupId' => self::GROUP_ID, 'keyId' => self::KEY_ID]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createColumn(array $config): Column
    {
        return new Column(
            key: self::COLUMN_KEY,
            locale: null,
            type: self::COLUMN_TYPE,
            group: null,
            config: $config,
        );
    }

    /**
     * The key's own field definition, which is what the export is typed on and formatted by.
     *
     * @throws Exception
     */
    private function createKeyFieldDefinitionResolver(): ServiceResolverInterface
    {
        return $this->makeEmpty(ServiceResolverInterface::class, [
            'getFieldDefinitionFromJson' => $this->makeEmpty(Data::class, [
                'getFieldType' => 'input',
            ]),
        ]);
    }

    /**
     * @throws Exception
     */
    private function createResolver(
        mixed $normalized = null,
        ?ServiceResolverInterface $classificationStoreServiceResolver = null,
        ?DataServiceInterface $dataService = null,
        ?ColumnReferenceServiceInterface $reference = null,
        ?KeyGroupRelation $relation = null
    ): ClassificationStoreResolver {
        // the real two: standing them in would leave what a cell and an export show for a list
        // untested here, which is the whole of what this resolver decides about one
        $keyQualifierResolver = new KeyQualifierResolver(new MultiValueServiceResolver());

        return new ClassificationStoreResolver(
            $this->createGridService($normalized),
            $this->makeEmpty(KeyRelationResolverInterface::class, [
                'getByGroup' => [$relation ?? $this->createRelation()],
            ]),
            $classificationStoreServiceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $dataService ?? $this->makeEmpty(DataServiceInterface::class),
            $keyQualifierResolver,
            new QualifiedValueSerializer(
                $keyQualifierResolver,
                new QualifiedValueParser($keyQualifierResolver),
                new EngineCapabilityGuard()
            ),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $reference ?? $this->makeEmpty(ColumnReferenceServiceInterface::class),
            $this->keyConfigResolver(),
        );
    }

    /**
     * The key configuration {@see ClassificationStoreResolver::resolveForExport()} loads through
     * `KeyConfig::getById()`, primed in the runtime cache the way an element is primed in
     * {@see \Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter\ReverseObjectRelationAdapterTest},
     * so that the export path is read without a database.
     *
     * @param array<string, mixed> $definition
     */
    private function primeKeyConfig(
        array $definition,
        string $type = 'input',
        int $keyId = self::KEY_ID
    ): void {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setType($type);
        $keyConfig->setDefinition((string) json_encode($definition));

        $cacheKey = 'cs_keyconfig_' . $keyId;
        $this->primedKeyConfigCacheKeys[] = $cacheKey;

        RuntimeCache::set($cacheKey, $keyConfig);
    }

    /**
     * An element whose classification store field holds the one slot under test.
     *
     * @throws Exception
     */
    /**
     * @param array<string, array<int, mixed>> $storedVariants the values core holds, by variant
     */
    private function createElementHolding(mixed $slot, array $storedVariants = []): Concrete
    {
        $element = new StoreFieldAwareObject();
        $element->setId(1);
        $element->classificationStore = $this->make(Classificationstore::class, [
            'getLocalizedKeyValue' => static fn (): mixed => $slot,
            'getKeyValues' => static fn (
                int $groupId,
                int $keyId,
                ?string $language = null,
                ?string $variant = null
            ): array => $storedVariants[$variant ?? 'default'] ?? [],
        ]);

        return $element;
    }

    /**
     * The export read, answering the value the resolver injected for the slot - which is what this
     * resolver decides; the field definition's own formatting of it is core's.
     *
     * @throws Exception
     */
    private function createExportRead(mixed &$injected, bool &$reached): DataServiceInterface
    {
        return $this->makeEmpty(DataServiceInterface::class, [
            'getExportFieldValue' => static function (
                Concrete $element,
                Data $fieldDefinition,
                string $key,
                ?FieldContextData $contextData = null
            ) use (&$injected, &$reached): string {
                $parameters = $contextData?->getLegacyParameters() ?? [];
                $reached = array_key_exists('injectedData', $parameters);
                $injected = $parameters['injectedData'] ?? null;

                return (string) $injected;
            },
        ]);
    }

    /**
     * The adapter resolver this one reads the normalized store from, answering one key's slot.
     *
     * @throws Exception
     */
    private function createGridService(mixed $normalized): GridServiceInterface
    {
        return $this->makeEmpty(GridServiceInterface::class, [
            'getColumnResolvers' => [
                'dataobject.adapter' => $this->makeEmpty(CoreElementColumnResolverInterface::class, [
                    'resolveForCoreElement' => new ColumnData(
                        key: 'cs',
                        locale: null,
                        value: [self::GROUP_ID => ['default' => [self::KEY_ID => $normalized]]],
                        fieldType: 'dataobject.classificationstore',
                    ),
                ]),
            ],
        ]);
    }

    private function createRelation(): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName('weight');
        $relation->setType('input');
        $relation->setDefinition('{"fieldtype":"input"}');

        return $relation;
    }
}
