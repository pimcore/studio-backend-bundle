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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\FieldDefinition\Parser\Resolver;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\LocalizedFieldResolver;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldService;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer;

/**
 * @internal
 */
final class LocalizedFieldResolverTest extends Unit
{
    public function testResolveLocalizedField(): void
    {
        $input = $this->createInput('name');
        $resolver = $this->createResolver([$input]);

        $wrapper = $resolver->resolve(['localizedfields', 'name']);

        $this->assertSame($input, $wrapper->getFieldDefinition());
        $this->assertSame('localizedfield', $wrapper->getContainerType());
        $this->assertSame('name', $wrapper->getFieldname());
    }

    public function testResolveBlockChildInsideLocalizedFields(): void
    {
        $relation = new ManyToManyObjectRelation();
        $relation->setName('companies');

        $resolver = $this->createResolver([
            $this->createBlock('partner', [$this->createInput('title'), $relation]),
        ]);

        $wrapper = $resolver->resolve(['localizedfields', 'partner', 'companies']);

        $this->assertSame($relation, $wrapper->getFieldDefinition());
        $this->assertSame('localizedfield', $wrapper->getContainerType());
        $this->assertSame('partner', $wrapper->getFieldname());
    }

    public function testResolveBlockChildInsideLayoutComponentInsideLocalizedFields(): void
    {
        $input = $this->createInput('nestedField');
        $fieldcontainer = new Fieldcontainer();
        $fieldcontainer->setName('fieldcontainer');
        $fieldcontainer->setChildren([$input]);

        $resolver = $this->createResolver([$this->createBlock('partner', [$fieldcontainer])]);

        $wrapper = $resolver->resolve(['localizedfields', 'partner', 'nestedField']);

        $this->assertSame($input, $wrapper->getFieldDefinition());
    }

    public function testResolveThrowsExceptionForUnknownBlockChildInsideLocalizedFields(): void
    {
        $resolver = $this->createResolver([
            $this->createBlock('partner', [$this->createInput('title')]),
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Block field definition "unknownField" does not exist');

        $resolver->resolve(['localizedfields', 'partner', 'unknownField']);
    }

    /**
     * @param Data[] $localizedChildren
     */
    private function createResolver(array $localizedChildren): LocalizedFieldResolver
    {
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren($localizedChildren);

        $resolver = new LocalizedFieldResolver(new LocalizedFieldService());
        $resolver->setFieldDefinitions(['localizedfields' => $localizedfields]);

        return $resolver;
    }

    /**
     * @param Data[]|Fieldcontainer[] $children
     */
    private function createBlock(string $name, array $children): Block
    {
        $block = new Block();
        $block->setName($name);
        $block->setChildren($children);

        return $block;
    }

    private function createInput(string $name): Input
    {
        $input = new Input();
        $input->setName($name);

        return $input;
    }
}
