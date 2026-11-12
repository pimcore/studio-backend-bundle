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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\BlockResolver;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\FieldCollectionResolver;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\LocalizedFieldServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Link;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldset;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

/**
 * @internal
 */
final class FieldCollectionResolverTest extends Unit
{
    private Select $select;

    private Block $block;

    protected function _before(): void
    {
        $this->select = new Select();
        $this->select->setName('buttonColor');

        $link = new Link();
        $link->setName('link');

        $fieldset = new Fieldset();
        $fieldset->setName('Button');
        $fieldset->setChildren([$link, $this->select]);

        $this->block = new Block();
        $this->block->setName('buttons');
        $this->block->setChildren([$fieldset]);
    }

    /**
     * @throws Exception
     */
    public function testResolvesFieldInsideBlockOfFieldCollection(): void
    {
        $resolver = $this->createResolver(['buttons' => $this->block], $this->block);

        $wrapper = $resolver->resolve(['content', 'UspArea', 'buttons', 'buttonColor']);

        $this->assertSame($this->select, $wrapper->getFieldDefinition());
        $this->assertSame('fieldcollection', $wrapper->getContainerType());
        $this->assertSame('buttons', $wrapper->getFieldname());
        $this->assertNull($wrapper->getSubContainerType());
    }

    /**
     * @throws Exception
     */
    public function testResolvesFieldInsideBlockInsideLocalizedFieldsOfFieldCollection(): void
    {
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren([$this->block]);

        $resolver = $this->createResolver(['localizedfields' => $localizedfields], $this->block);

        $wrapper = $resolver->resolve(['content', 'UspArea', 'localizedfields', 'buttons', 'buttonColor']);

        $this->assertSame($this->select, $wrapper->getFieldDefinition());
        $this->assertSame('fieldcollection', $wrapper->getContainerType());
        $this->assertSame('localizedfields', $wrapper->getFieldname());
        $this->assertSame('localizedfield', $wrapper->getSubContainerType());
        $this->assertSame('buttons', $wrapper->getSubContainerKey());
    }

    /**
     * @param array<string, Data> $fieldCollectionFields
     *
     * @throws Exception
     */
    private function createResolver(array $fieldCollectionFields, Data $localizedFieldDefinition): FieldCollectionResolver
    {
        $definition = $this->makeEmpty(Definition::class, [
            'getFieldDefinition' => static fn (string $key) => $fieldCollectionFields[$key] ?? null,
            'getFieldDefinitions' => $fieldCollectionFields,
        ]);

        $definitionResolver = $this->makeEmpty(DefinitionResolverInterface::class, [
            'getByKey' => $definition,
        ]);

        $localizedFieldService = $this->makeEmpty(LocalizedFieldServiceInterface::class, [
            'getFieldDefinition' => $localizedFieldDefinition,
        ]);

        $fieldCollections = new Fieldcollections();
        $fieldCollections->setName('content');

        $resolver = new FieldCollectionResolver($definitionResolver, $localizedFieldService);
        $resolver->setFieldDefinitions(['content' => $fieldCollections]);
        $resolver->setResolvers(['block' => new BlockResolver($localizedFieldService)]);

        return $resolver;
    }
}
