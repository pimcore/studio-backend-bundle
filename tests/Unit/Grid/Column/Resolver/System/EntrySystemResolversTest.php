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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\System;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject as StudioDataObject;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\System\IntegerResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\System\UserResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\DataObject\Concrete;

/**
 * The index entry of a data object row carries the same system values as the object, so a grid of system
 * columns can be built without loading the object. These resolvers have to give the same value for both.
 *
 * @internal
 */
final class EntrySystemResolversTest extends Unit
{
    /**
     * @dataProvider integerAndUserColumnProvider
     */
    public function testTheEntryGivesTheSameColumnDataAsTheObject(
        CoreElementColumnResolverInterface&StudioElementColumnResolverInterface $resolver,
        string $key,
        string $type,
        int|null $value
    ): void {
        $getter = 'get' . ucfirst($key);
        $object = $this->makeEmpty(Concrete::class, [$getter => $value]);
        $entry = $this->makeEmpty(StudioDataObject::class, [$getter => $value]);
        $column = new Column(key: $key, locale: null, type: $type, group: ['system'], config: []);

        $fromEntry = $resolver->resolveForStudioElement($column, $entry);

        $this->assertEquals($resolver->resolveForCoreElement($column, $object), $fromEntry);
        $this->assertSame($value, $fromEntry->getValue());
        $this->assertSame($type, $fromEntry->getFieldType());
    }

    public static function integerAndUserColumnProvider(): array
    {
        return [
            'index' => [new IntegerResolver(), 'index', 'system.integer', 3],
            'user owner' => [new UserResolver(), 'userOwner', 'system.user', 22],
            'user modification' => [new UserResolver(), 'userModification', 'system.user', 5],
            'user modification of an untouched object' => [
                new UserResolver(),
                'userModification',
                'system.user',
                null,
            ],
        ];
    }
}
