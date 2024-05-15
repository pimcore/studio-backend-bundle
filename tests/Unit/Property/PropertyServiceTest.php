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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\ElementProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyService;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;

final class PropertyServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testElementProperties(): void
    {
        $element = $this->getDocumentElement();
        $service = $this->getService();
        $properties = $service->getElementProperties('document', $element->getId());

        $this->assertSame('name', $properties[0]->getKey());
        $this->assertSame('test', $properties[0]->getData());
        $this->assertSame('text', $properties[0]->getType());
        $this->assertTrue($properties[0]->isInheritable());
        $this->assertFalse($properties[0]->isInherited());
        $this->assertNull($properties[0]->getConfig());
        $this->assertSame('Custom', $properties[0]->getPredefinedName());
        $this->assertSame('Test description', $properties[0]->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testCreatePredefinedProperty(): void
    {
        $service = $this->getService();
        $property = $service->createPredefinedProperty();

        $this->assertSame('new_id', $property->getId());
        $this->assertSame('text', $property->getType());
        $this->assertSame('New Property', $property->getName());
        $this->assertSame('new_key', $property->getKey());
        $this->assertSame('New Description', $property->getDescription());
        $this->assertTrue($property->getInheritable());
        $this->assertNotNull($property->getCreationDate());
        $this->assertNotNull($property->getModificationDate());
    }

    /**
     * @throws Exception
     */
    public function testPredefinedListing(): void
    {
        $service = $this->getService();
        $predefinedProperty = $service->getPredefinedProperty($this->getPredefinedProperty());

        $this->assertSame('new_id', $predefinedProperty->getId());
        $this->assertSame('New Property', $predefinedProperty->getName());
        $this->assertSame('New Description', $predefinedProperty->getDescription());
        $this->assertSame('new_key', $predefinedProperty->getKey());
        $this->assertSame('text', $predefinedProperty->getType());
        $this->assertNull($predefinedProperty->getData());
        $this->assertNull($predefinedProperty->getConfig());
        $this->assertSame('document', $predefinedProperty->getCtype());
        $this->assertTrue($predefinedProperty->isInheritable());
        $this->assertNotNull($predefinedProperty->getCreationDate());
        $this->assertNotNull($predefinedProperty->getModificationDate());
    }

    /**
     * @throws Exception
     */
    private function getService(): PropertyServiceInterface
    {
        return new PropertyService(
            $this->getRepository(),
            $this->getServiceResolver(),
            $this->getPropertyHydrator()
        );
    }

    /**
     * @throws Exception
     */
    private function getServiceResolver(): ServiceResolverInterface
    {
        return $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => $this->getDocumentElement(),
        ]);
    }

    /**
     * @throws Exception
     */
    private function getRepository(): PropertyRepositoryInterface
    {
        return $this->makeEmpty(
            PropertyRepositoryInterface::class,
            ['createPredefinedProperty' => $this->getPredefinedProperty()]
        );
    }

    /**
     * @throws Exception
     */
    private function getPropertyHydrator(): PropertyHydratorInterface
    {
        return $this->makeEmpty(PropertyHydratorInterface::class,
            [
                'hydrateElementProperty' => new ElementProperty(
                    'name',
                    'test',
                    'text',
                    true,
                    false,
                    null,
                    'Custom',
                    'Test description'
                ),
                'hydratePredefinedProperty' => new PredefinedProperty(
                    'new_id',
                    'New Property',
                    'New Description',
                    'new_key',
                    'text',
                    null,
                    null,
                    'document',
                    true,
                    time(),
                    time()
                ),
            ]
        );
    }

    private function getPredefinedProperty(): Predefined
    {
        $property = new Predefined();
        $property->setId('new_id');
        $property->setCtype('document');
        $property->setName('New Property');
        $property->setKey('new_key');
        $property->setType('text');
        $property->setCreationDate(time());
        $property->setModificationDate(time());
        $property->setInheritable(true);
        $property->setDescription('New Description');

        return $property;
    }

    /**
     * @throws Exception
     */
    private function getDocumentElement(): Document
    {
        return $this->makeEmpty(Document::class,
            [
                'getProperties' => $this->getProperties(),
                'getId' => 1,
            ]
        );
    }

    private function getProperties(): array
    {
        $property =  new Property();
        $property->setDataFromResource($this->getDocument());
        $property->setName('New Property');
        $property->setType('text');
        $property->setCtype('document');
        $property->setInheritable(true);
        $property->setInherited(true);

        $propertyTwo = clone $property;
        $propertyTwo->setName('New Property Two');
        $propertyTwo->setData('2');

        return [
            $property,
            $propertyTwo,
        ];
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
