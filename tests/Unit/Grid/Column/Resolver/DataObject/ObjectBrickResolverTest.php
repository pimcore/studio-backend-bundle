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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ObjectBrickResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ObjectBrickResolverTest extends Unit
{
    /**
     * A brick that is allowed on the field but not filled in on the object is returned as null by
     * the container getter. Exporting a localized attribute of it must yield an empty cell instead
     * of a "call to a member function get() on null" error that kills the export job.
     */
    public function testResolveForExportReturnsNullForEmptyBrickWithLocale(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.name',
            locale: 'en',
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );

        $result = $this->createResolver()->resolveForExport(
            $column,
            $this->createElementWithEmptyBrick(),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('input', $result->getFieldType());
        $this->assertSame('en', $result->getLocale());
    }

    public function testResolveForExportReturnsNullForEmptyBrickWithoutLocale(): void
    {
        $column = new Column(
            key: 'bricks.MyBrick.name',
            locale: null,
            type: 'dataobject.objectbrick',
            group: ['objectbrick'],
            config: [],
        );

        $result = $this->createResolver()->resolveForExport(
            $column,
            $this->createElementWithEmptyBrick(),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('input', $result->getFieldType());
    }

    private function createElementWithEmptyBrick(): Concrete
    {
        $brickContainer = $this->makeEmpty(Objectbrick::class, ['get' => null]);

        return $this->makeEmpty(Concrete::class, ['get' => $brickContainer]);
    }

    private function createResolver(): ObjectBrickResolver
    {
        $fieldDefinition = new Input();
        $fieldDefinition->setName('name');

        return new ObjectBrickResolver(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class, [
                'getExportFieldValue' => Expected::never(),
            ]),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(ObjectBrickServiceInterface::class),
            $this->makeEmpty(DefinitionResolverInterface::class, [
                'getByKey' => $this->makeEmpty(Definition::class, ['getFieldDefinition' => $fieldDefinition]),
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
        );
    }
}
