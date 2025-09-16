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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Property;

use PHPUnit\Framework\TestCase;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Property\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Resolver\Element\ReferenceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydrator
 */
final class PropertyHydratorTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testHydratePredefined(): void
    {
        $hydrator = $this->getHydrator();

        $data = $hydrator->hydratePredefinedProperty($this->getPredefined());

        $this->assertSame('new_id', $data->getId());
        $this->assertSame(ElementTypes::TYPE_DOCUMENT, $data->getCtype());
        $this->assertSame('New Property', $data->getName());
        $this->assertSame('new_key', $data->getKey());
        $this->assertSame('text', $data->getType());
        $this->assertTrue($data->isInheritable());
        $this->assertSame('New Description', $data->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testHydrateElementProperties(): void
    {
        $hydrator = $this->getHydrator();
        $data = $hydrator->hydrateElementProperty($this->getElementProperty());

        $this->assertSame('New Property', $data->getPredefinedName());
        $this->assertSame('text', $data->getType());
        $this->assertTrue($data->isInheritable());
        $this->assertTrue($data->isInherited());
        $this->assertNull($data->getConfig());

        $this->assertIsArray($data->getData());
        $this->assertArrayHasKey('path', $data->getData());
        $this->assertArrayHasKey('id', $data->getData());
        $this->assertArrayHasKey('type', $data->getData());
        $this->assertArrayHasKey('key', $data->getData());

        $this->assertSame('/test', $data->getData()['path']);
        $this->assertSame(1, $data->getData()['id']);
        $this->assertSame('page', $data->getData()['type']);
        $this->assertSame('test', $data->getData()['key']);
    }

    /**
     * @throws Exception
     */
    private function getHydrator(): PropertyHydratorInterface
    {
        return new PropertyHydrator($this->mockPredefinedResolver(), $this->mockDataResolver());
    }

    /**
     * @throws Exception
     */
    private function mockPredefinedResolver(): PredefinedResolverInterface
    {
        $mock = $this->createMock(PredefinedResolverInterface::class);
        $mock->method('getById')->willReturn($this->getPredefined());
        $mock->method('getByKey')->willReturn($this->getPredefined());
        
        return $mock;
    }

    /**
     * @throws Exception
     */
    private function mockDataResolver(): ReferenceResolverInterface
    {
        $mock = $this->createMock(ReferenceResolverInterface::class);
        $mock->method('resolve')->willReturn([
            'path' => '/test',
            'id' => 1,
            'type' => 'page',
            'key' => 'test',
        ]);
        
        return $mock;
    }

    private function getPredefined(): Predefined
    {
        $property = new Predefined();
        $property->setId('new_id');
        $property->setCtype(ElementTypes::TYPE_DOCUMENT);
        $property->setName('New Property');
        $property->setKey('new_key');
        $property->setType('text');
        $property->setCreationDate(time());
        $property->setModificationDate(time());
        $property->setInheritable(true);
        $property->setDescription('New Description');

        return $property;
    }

    private function getElementProperty(): Property
    {
        $property =  new Property();
        $property->setDataFromResource($this->getDocument());
        $property->setName('New Property');
        $property->setType('text');
        $property->setCtype('document');
        $property->setInheritable(true);
        $property->setInherited(true);

        return $property;
    }

    private function getDocument(): Document
    {
        $document = new Document();
        $document->setPath('/test');
        $document->setId(1);
        $document->setType('page');
        $document->setKey('test');

        return $document;
    }
}
