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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\PropertyNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Property\Event\ElementPropertyEvent;
use Pimcore\Bundle\StudioBackendBundle\Property\Event\PredefinedPropertyEvent;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\ElementProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class PropertyService implements PropertyServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private PropertyHydratorInterface $propertyHydrator,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function createPredefinedProperty(): PredefinedProperty
    {
        $predefined = $this->propertyRepository->createPredefinedProperty();
        return $this->getPredefinedProperty($predefined->getId());
    }

    public function getPredefinedProperty(string $id): PredefinedProperty
    {
        return $this->propertyHydrator->hydratePredefinedProperty(
            $this->propertyRepository->getPredefinedProperty($id)
        );
    }

    /**
     * @return array<int, PredefinedProperty>
     */
    public function getPredefinedProperties(PropertiesParameters $parameters): array
    {
        $properties = $this->propertyRepository->listProperties($parameters);
        $hydratedProperties = [];
        foreach ($properties->load() as $property) {

            $predefinedProperty = $this->propertyHydrator->hydratePredefinedProperty($property);

            $this->eventDispatcher->dispatch(
                new PredefinedPropertyEvent($predefinedProperty),
                PredefinedPropertyEvent::EVENT_NAME
            );

            $hydratedProperties[] = $predefinedProperty;
        }
        return $hydratedProperties;
    }

    /**
     * @return array<int, ElementProperty>
     */
    public function getElementProperties(string $elementType, int $id): array
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        $hydratedProperties = [];

        foreach($element->getProperties() as $property) {

            $elementProperty = $this->propertyHydrator->hydrateElementProperty($property);

            $this->eventDispatcher->dispatch(
                new ElementPropertyEvent($elementProperty),
                ElementPropertyEvent::EVENT_NAME
            );

            $hydratedProperties[] = $elementProperty;
        }

        return $hydratedProperties;
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): void
    {
        $this->propertyRepository->updatePredefinedProperty($id, $property);
    }

    public function updateElementProperties(string $elementType, int $id, UpdateElementProperties $items): void
    {
        $this->propertyRepository->updateElementProperties($elementType, $id, $items);
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function deletePredefinedProperty(string $id): void
    {
        $this->propertyRepository->deletePredefinedProperty($id);
    }
}
