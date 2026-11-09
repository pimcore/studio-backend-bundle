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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializer;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ClassificationStoreResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
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

    protected function _after(): void
    {
        RuntimeCache::getInstance()->offsetUnset(self::KEY_CONFIG_CACHE_KEY);
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
     * One grid cell holds one value, so a key serving a list of qualified ones shows its default
     * slot rather than being handed the whole list - the cell is typed on the key's own field type,
     * which cannot render a list of entry objects.
     *
     * @dataProvider cellValueProvider
     *
     * @param array<string, mixed> $declaration
     *
     * @throws Exception
     */
    public function testACellShowsTheDefaultSlotOfTheKeysSlot(array $declaration, mixed $normalized, mixed $expected): void
    {
        $this->primeKeyConfig($declaration);

        $result = $this->createResolver($normalized)->resolveForCoreElement(
            $this->createKeyColumn(),
            $this->makeEmpty(Concrete::class, ['getId' => 1])
        );

        $this->assertSame($expected, $result->getValue());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: mixed, 2: mixed}>
     */
    public static function cellValueProvider(): array
    {
        $declaring = ['variants' => ['metric', 'imperial'], 'multiValue' => true];
        $plain = ['fieldtype' => 'input'];

        return [
            'a list of qualified entries' => [
                $declaring,
                [['v' => '176', 'var' => 'imperial'], ['v' => '80']],
                '80',
            ],
            // every key that declares nothing keeps reaching the cell untouched
            'a single value' => [$plain, '80', '80'],
            'a multi select value' => [$plain, ['red', 'green'], ['red', 'green']],
            'the string zero' => [$plain, '0', '0'],
            'an empty slot' => [$plain, null, null],
            'an empty qualified slot' => [$declaring, null, null],
        ];
    }

    /**
     * The cell and the export of one column show the same value, asserted over one fixture rather
     * than separately: the row holds a list, the cell is handed what the adapter publishes for it
     * under each declaration, and the export is handed the one value it injects for the same row.
     *
     * The declaration alone decides what each shows, so a key that has stopped declaring shows its
     * default entry in both and a narrowed one never shows the entry it hides.
     *
     * @dataProvider slotHoldingEntriesProvider
     *
     * @param array<string, mixed> $declaration
     *
     * @throws Exception
     */
    public function testTheCellAndTheExportOfOneColumnShowTheSameValue(array $declaration, mixed $published): void
    {
        $slot = [['v' => '176', 'var' => 'imperial'], ['v' => '80']];
        $this->primeKeyConfig($declaration);

        $injected = null;
        $reached = false;
        $resolver = $this->createResolver(
            $published,
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );
        $element = $this->createElementHolding($slot);

        $cell = $resolver->resolveForCoreElement($this->createKeyColumn(), $element);
        $export = $resolver->resolveForExport(
            $this->createKeyColumn(),
            $element,
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame('80', $injected);
        $this->assertSame($cell->getValue(), $export->getValue());
    }

    /**
     * The declarations a key can carry while its slot holds a list, each with what the adapter
     * publishes for that row - including neither member, and a declaration that has narrowed past
     * the imperial entry, which are the two the declaration hides entries under.
     *
     * @return array<string, array{0: array<string, mixed>, 1: mixed}>
     */
    public static function slotHoldingEntriesProvider(): array
    {
        return [
            'a key declaring its qualifiers' => [
                ['variants' => ['metric', 'imperial'], 'multiValue' => true],
                [['v' => '176', 'var' => 'imperial'], ['v' => '80']],
            ],
            'a key that has narrowed its variants' => [['variants' => ['metric']], [['v' => '80']]],
            'a key that has stopped declaring them' => [['fieldtype' => 'input'], '80'],
        ];
    }

    /**
     * An entry the declaration hides is shown by neither: a row holding nothing but one hands the
     * export no value, where a fallback to "the first entry" would publish what the cell hides.
     *
     * @throws Exception
     */
    public function testAHiddenEntryIsNeitherShownInTheCellNorInjectedIntoTheExport(): void
    {
        $this->primeKeyConfig(['fieldtype' => 'input']);

        $injected = 'untouched';
        $reached = false;
        $resolver = $this->createResolver(
            null,
            $this->createKeyFieldDefinitionResolver(),
            $this->createExportRead($injected, $reached)
        );

        $resolver->resolveForExport(
            $this->createKeyColumn(),
            $this->createElementHolding([['v' => '176', 'var' => 'imperial']]),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertTrue($reached);
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
        $this->primeKeyConfig(['variants' => ['metric'], 'multiValue' => true]);

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

        // nothing is stored at a field the class does not have, so nothing is injected
        $this->assertFalse($reached);
        $this->assertNull($injected);
        $this->assertSame('', $result->getValue());
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
        ?DataServiceInterface $dataService = null
    ): ClassificationStoreResolver {
        // the real two: standing them in would leave what a cell and an export show for a list
        // untested here, which is the whole of what this resolver decides about one
        $keyQualifierResolver = new KeyQualifierResolver();

        return new ClassificationStoreResolver(
            $this->createGridService($normalized),
            $this->makeEmpty(KeyRelationResolverInterface::class, [
                'getByGroup' => [$this->createRelation()],
            ]),
            $classificationStoreServiceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $dataService ?? $this->makeEmpty(DataServiceInterface::class),
            $keyQualifierResolver,
            new QualifiedValueSerializer($keyQualifierResolver),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
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
    private function primeKeyConfig(array $definition): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setType('input');
        $keyConfig->setDefinition((string) json_encode($definition));

        RuntimeCache::set(self::KEY_CONFIG_CACHE_KEY, $keyConfig);
    }

    /**
     * An element whose classification store field holds the one slot under test.
     *
     * @throws Exception
     */
    private function createElementHolding(mixed $slot): Concrete
    {
        $element = new StoreFieldAwareObject();
        $element->setId(1);
        $element->classificationStore = $this->make(Classificationstore::class, [
            'getLocalizedKeyValue' => static fn (): mixed => $slot,
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

        return $relation;
    }
}
