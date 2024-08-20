<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Property;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Property\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Resolver\Element\ReferenceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;

final class PropertyHydratorTest extends Unit
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
        return $this->makeEmpty(
            PredefinedResolverInterface::class,
            [
                'getById' => $this->getPredefined(),
                'getByKey' => $this->getPredefined(),
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function mockDataResolver(): ReferenceResolverInterface
    {
        return $this->makeEmpty(
            ReferenceResolverInterface::class,
            [
                'resolve' => [
                    'path' => '/test',
                    'id' => 1,
                    'type' => 'page',
                    'key' => 'test',
                ],
            ]
        );
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
