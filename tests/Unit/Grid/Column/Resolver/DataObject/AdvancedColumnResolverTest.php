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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdvancedColumnResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\ResolverTypeGuesserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedColumnSourceFieldContext;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class AdvancedColumnResolverTest extends Unit
{
    public function testResolveForCoreElementResolvesClassificationStoreField(): void
    {
        $classificationStoreResolver = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => static function (Column $column): ColumnData {
                // The advanced resolver must forward the picked group/key as the sub column config
                self::assertSame('dataobject.classificationstore', $column->getType());
                self::assertSame('csstore', $column->getKey());
                self::assertSame(['groupId' => 5, 'keyId' => 7], $column->getConfig());

                return new ColumnData(
                    key: 'csstore.size',
                    locale: null,
                    value: 'XL',
                    fieldType: 'input',
                );
            },
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => []]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.classificationstore' => $classificationStoreResolver],
                'isLocaleViewableForElement' => true,
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.classificationstore',
                'isLocalizable' => false,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $column = new Column(
            key: 'advanced',
            locale: null,
            type: 'dataobject.advanced',
            group: ['advanced'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => ['field' => 'csstore', 'groupId' => 5, 'keyId' => 7],
                    ],
                ],
                'transformers' => [],
            ],
        );

        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);

        $result = $resolver->resolveForCoreElement($column, $element);

        $values = $result->getValue();
        $this->assertIsArray($values);
        $this->assertCount(1, $values);
        $this->assertInstanceOf(AdvancedValue::class, $values[0]);
        $this->assertSame('XL', $values[0]->getValue());
        $this->assertSame('input', $values[0]->getType());
        $this->assertSame('csstore', $values[0]->getFieldName());
        $this->assertNull($values[0]->getRelation());
    }

    /**
     * A denied locale for a simple field must skip that field entirely and never reach its
     * sub-resolver - otherwise the "advanced" column would leak values for languages a role's
     * "Viewable Languages" workspace permission restricts.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testResolveForCoreElementSkipsFieldWhenLocaleNotViewable(): void
    {
        $subResolver = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => Expected::never(),
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => []]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.input' => $subResolver],
                'isLocaleViewableForElement' => false,
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.input',
                'isLocalizable' => true,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $column = new Column(
            key: 'advanced',
            locale: 'de',
            type: 'dataobject.advanced',
            group: ['advanced'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => ['field' => 'name'],
                    ],
                ],
                'transformers' => [],
            ],
        );

        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);

        $result = $resolver->resolveForCoreElement($column, $element);

        self::assertSame([], $result->getValue());
    }

    /**
     * A localizable sub-field that inherits a null locale from its parent column is implicitly
     * read in the request/default locale by core. The resolver must tell the grid service that
     * the field is localized so the implicit locale gets authorized - passing a null locale
     * without that flag would silently skip the language permission check.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1063
     */
    public function testResolveForCoreElementFlagsLocalizableFieldWithoutExplicitLocale(): void
    {
        $capturedArgs = null;

        $subResolver = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => Expected::never(),
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => []]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.input' => $subResolver],
                'isLocaleViewableForElement' => static function (
                    $element,
                    ?string $locale,
                    $user = null,
                    bool $isLocalizedField = false
                ) use (&$capturedArgs): bool {
                    $capturedArgs = ['locale' => $locale, 'isLocalizedField' => $isLocalizedField];

                    return false;
                },
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.input',
                'isLocalizable' => true,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $column = new Column(
            key: 'advanced',
            locale: null,
            type: 'dataobject.advanced',
            group: ['advanced'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => ['field' => 'name'],
                    ],
                ],
                'transformers' => [],
            ],
        );

        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);

        $result = $resolver->resolveForCoreElement($column, $element);

        self::assertSame([], $result->getValue());
        self::assertSame(['locale' => null, 'isLocalizedField' => true], $capturedArgs);
    }

    /**
     * The interactive Studio grid's "jump to the default language" nicety
     * ({@see \Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait::getLocalizedValue()})
     * must not leak into an advanced column's transformer pipeline: it would make the same source
     * field resolve differently depending only on whether a transformer happens to be attached.
     * {@see AdvancedColumnResolver} must mark itself as "resolving a source field" for the whole
     * duration of its own pipeline resolution, and clear it again afterwards, regardless of which
     * sub-resolver method ends up being called.
     */
    public function testResolveForExportMarksSourceFieldContextWhileResolvingTransformerPipeline(): void
    {
        $sourceFieldContext = new AdvancedColumnSourceFieldContext();
        $observedWhileResolving = null;

        $subResolver = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => function () use (
                $sourceFieldContext,
                &$observedWhileResolving
            ): ColumnData {
                $observedWhileResolving = $sourceFieldContext->isResolvingSourceField();

                return new ColumnData(key: 'description', locale: 'de', value: 'raw value', fieldType: 'input');
            },
        ]);

        $transformer = $this->makeEmpty(TransformerInterface::class, [
            'transform' => static fn (array $value): array => $value,
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => ['noop' => $transformer]]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.input' => $subResolver],
                'isLocaleViewableForElement' => true,
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.input',
                'isLocalizable' => true,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $sourceFieldContext,
        );

        $column = $this->makeAdvancedColumnWithTransformer('description');
        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);
        $user = $this->makeEmpty(UserInterface::class);

        self::assertFalse($sourceFieldContext->isResolvingSourceField());

        $resolver->resolveForExport($column, $element, $user);

        self::assertTrue(
            $observedWhileResolving,
            'the source field context must be active while the pipeline resolves its source field'
        );
        self::assertFalse(
            $sourceFieldContext->isResolvingSourceField(),
            'the source field context must be cleared again once resolution finishes'
        );
    }

    /**
     * An advanced column must resolve to the same value whether or not it has a transformer
     * attached: without a transformer the export resolver is called directly, with one the core
     * (raw-value) resolver is called and the pipeline runs afterwards. A no-op pipeline step must
     * not change the result.
     */
    public function testResolveForExportGivesIdenticalResultWithAndWithoutTransformer(): void
    {
        $subResolver = new class implements CoreElementColumnResolverInterface, ExportResolverInterface {
            public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
            {
                return new ColumnData(
                    key: 'description',
                    locale: $column->getLocale(),
                    value: 'configured fallback text',
                    fieldType: 'input'
                );
            }

            public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
            {
                return new ColumnData(
                    key: 'description',
                    locale: $column->getLocale(),
                    value: 'configured fallback text',
                    fieldType: 'input'
                );
            }
        };

        $transformer = $this->makeEmpty(TransformerInterface::class, [
            'transform' => static fn (array $value): array => $value,
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => ['noop' => $transformer]]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.input' => $subResolver],
                'isLocaleViewableForElement' => true,
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.input',
                'isLocalizable' => true,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);
        $user = $this->makeEmpty(UserInterface::class);

        $withoutTransformer = $resolver->resolveForExport(
            $this->makeAdvancedColumn('description', withTransformer: false),
            $element,
            $user
        );
        $withTransformer = $resolver->resolveForExport(
            $this->makeAdvancedColumn('description', withTransformer: true),
            $element,
            $user
        );

        self::assertSame('configured fallback text', $withoutTransformer->getValue());
        self::assertSame($withoutTransformer->getValue(), $withTransformer->getValue());
    }

    /**
     * A source field with no value at the resolved locale (and no fallback) resolves to `null`.
     * `null` is not string-convertible, so the export loop used to fall through to
     * `json_encode(null)`, which is literally the four characters "null" - indistinguishable from a
     * legitimate value. It must export as an empty string instead.
     */
    public function testResolveForExportConvertsMissingSourceValueToEmptyStringNotLiteralNull(): void
    {
        $result = $this->resolveExportForSingleValue(null);

        self::assertSame('', $result->getValue());
    }

    /**
     * A `null` nested inside an array (e.g. one item of a multi-value source field left empty) is a
     * different case from a bare top-level `null`: it is standard, unambiguous JSON, not the
     * literal string "null" the export loop must avoid for a missing scalar value.
     */
    public function testResolveForExportKeepsNullInsideAnArrayAsJson(): void
    {
        $result = $this->resolveExportForSingleValue(['a', null]);

        self::assertSame('["a",null]', $result->getValue());
    }

    /**
     * {@see AdvancedColumnResolver::resolveForCoreElement()} never stringifies its values - it
     * returns the raw {@see AdvancedValue} list as-is, so a missing source value stays a real PHP
     * `null` there and the "null"-string bug never applied to this path in the first place.
     */
    public function testResolveForCoreElementKeepsRawNullValue(): void
    {
        $subResolver = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => static fn (): ColumnData => new ColumnData(
                key: 'description',
                locale: 'de',
                value: null,
                fieldType: 'input',
            ),
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => []]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.input' => $subResolver],
                'isLocaleViewableForElement' => true,
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.input',
                'isLocalizable' => true,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $column = $this->makeAdvancedColumn('description', withTransformer: false);
        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);

        $result = $resolver->resolveForCoreElement($column, $element);

        $values = $result->getValue();
        self::assertCount(1, $values);
        self::assertInstanceOf(AdvancedValue::class, $values[0]);
        self::assertNull($values[0]->getValue());
    }

    private function resolveExportForSingleValue(mixed $sourceValue): ColumnData
    {
        $subResolver = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => static fn (): ColumnData => new ColumnData(
                key: 'description',
                locale: 'de',
                value: $sourceValue,
                fieldType: 'input',
            ),
        ]);

        $transformer = $this->makeEmpty(TransformerInterface::class, [
            'transform' => static fn (array $value): array => $value,
        ]);

        $resolver = new AdvancedColumnResolver(
            $this->makeEmpty(TransformerLoaderInterface::class, ['loadTransformers' => ['noop' => $transformer]]),
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.input' => $subResolver],
                'isLocaleViewableForElement' => true,
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.input',
                'isLocalizable' => true,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $column = $this->makeAdvancedColumnWithTransformer('description');
        $element = $this->makeEmpty(Concrete::class, ['getClassId' => 'CAR']);
        $user = $this->makeEmpty(UserInterface::class);

        return $resolver->resolveForExport($column, $element, $user);
    }

    private function makeAdvancedColumnWithTransformer(string $field): Column
    {
        return $this->makeAdvancedColumn($field, withTransformer: true);
    }

    private function makeAdvancedColumn(string $field, bool $withTransformer): Column
    {
        return new Column(
            key: 'advanced',
            locale: 'de',
            type: 'dataobject.advanced',
            group: ['advanced'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => ['field' => $field],
                    ],
                ],
                'transformers' => $withTransformer ? [['key' => 'noop', 'config' => []]] : [],
            ],
        );
    }
}
