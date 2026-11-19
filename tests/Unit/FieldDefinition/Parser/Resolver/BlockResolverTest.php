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
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\BlockResolver;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer;

/**
 * @internal
 */
final class BlockResolverTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testResolveDirectBlockChild(): void
    {
        $resolver = $this->createResolver([
            'testBlock' => $this->createBlock('testBlock', [$this->createInput('directField')]),
        ]);

        $wrapper = $resolver->resolve(['testBlock', 'directField']);

        $this->assertSame('directField', $wrapper->getFieldDefinition()->getName());
        $this->assertSame('block', $wrapper->getContainerType());
        $this->assertSame('directField', $wrapper->getFieldname());
        $this->assertNull($wrapper->getSubContainerType());
        $this->assertNull($wrapper->getSubContainerKey());
    }

    /**
     * @throws Exception
     */
    public function testResolveBlockChildInsideLayoutComponent(): void
    {
        $select = new Select();
        $select->setName('selectField');

        $resolver = $this->createResolver([
            'testBlock' => $this->createBlock('testBlock', [
                $this->createInput('directField'),
                $this->createFieldcontainer([$select]),
            ]),
        ]);

        $wrapper = $resolver->resolve(['testBlock', 'selectField']);

        $this->assertSame($select, $wrapper->getFieldDefinition());
        $this->assertSame('block', $wrapper->getContainerType());
        $this->assertSame('selectField', $wrapper->getFieldname());
    }

    /**
     * @throws Exception
     */
    public function testResolveBlockChildInsideNestedLayoutComponents(): void
    {
        $input = $this->createInput('nestedField');

        $resolver = $this->createResolver([
            'testBlock' => $this->createBlock('testBlock', [
                $this->createFieldcontainer([$this->createFieldcontainer([$input])]),
            ]),
        ]);

        $wrapper = $resolver->resolve(['testBlock', 'nestedField']);

        $this->assertSame($input, $wrapper->getFieldDefinition());
    }

    /**
     * @throws Exception
     */
    public function testResolveLocalizedBlockChildInsideLayoutComponent(): void
    {
        $localizedField = $this->createInput('localizedField');
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren([$localizedField]);

        $resolver = $this->createResolver(
            [
                'testBlock' => $this->createBlock('testBlock', [
                    $this->createFieldcontainer([$localizedfields]),
                ]),
            ],
            $localizedField
        );

        $wrapper = $resolver->resolve(['testBlock', 'localizedfields', 'localizedField']);

        $this->assertSame($localizedField, $wrapper->getFieldDefinition());
        $this->assertSame('block', $wrapper->getContainerType());
        $this->assertSame('localizedfields', $wrapper->getFieldname());
        $this->assertSame('localizedfield', $wrapper->getSubContainerType());
        $this->assertSame('localizedField', $wrapper->getSubContainerKey());
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForUnknownBlockChild(): void
    {
        $resolver = $this->createResolver([
            'testBlock' => $this->createBlock('testBlock', [
                $this->createFieldcontainer([$this->createInput('knownField')]),
            ]),
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Block field definition "unknownField" does not exist');

        $resolver->resolve(['testBlock', 'unknownField']);
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsExceptionForNonBlockField(): void
    {
        $resolver = $this->createResolver(['testInput' => $this->createInput('testInput')]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Class Definition has to be of type Block');

        $resolver->resolve(['testInput', 'directField']);
    }

    /**
     * @param array<string, Data> $fieldDefinitions
     *
     * @throws Exception
     */
    private function createResolver(
        array $fieldDefinitions,
        ?Data $localizedFieldDefinition = null
    ): BlockResolver {
        $localizedFieldService = $this->makeEmpty(
            LocalizedFieldServiceInterface::class,
            ['getFieldDefinition' => $localizedFieldDefinition ?? $this->createInput('unused')]
        );

        $resolver = new BlockResolver($localizedFieldService);
        $resolver->setFieldDefinitions($fieldDefinitions);

        return $resolver;
    }

    /**
     * @param array<Data|Layout> $children
     */
    private function createBlock(string $name, array $children): Block
    {
        $block = new Block();
        $block->setName($name);
        $block->setChildren($children);

        return $block;
    }

    /**
     * @param array<Data|Layout> $children
     */
    private function createFieldcontainer(array $children): Fieldcontainer
    {
        $fieldcontainer = new Fieldcontainer();
        $fieldcontainer->setName('fieldcontainer');
        $fieldcontainer->setChildren($children);

        return $fieldcontainer;
    }

    private function createInput(string $name): Input
    {
        $input = new Input();
        $input->setName($name);

        return $input;
    }
}
