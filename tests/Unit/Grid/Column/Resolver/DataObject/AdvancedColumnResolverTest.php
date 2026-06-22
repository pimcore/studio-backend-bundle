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
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdvancedColumnResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\ResolverTypeGuesserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Model\DataObject\Concrete;

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
            ]),
            $this->makeEmpty(ResolverTypeGuesserInterface::class, [
                'guessType' => 'dataobject.classificationstore',
                'isLocalizable' => false,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
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
}
