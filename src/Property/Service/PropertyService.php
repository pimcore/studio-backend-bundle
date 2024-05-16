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
use Pimcore\Bundle\StudioBackendBundle\Event\Service\EventDispatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\PropertyNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\ElementProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

/**
 * @internal
 */
final readonly class PropertyService implements PropertyServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private PropertyRepositoryInterface $repository,
        private PropertyHydratorInterface $propertyHydrator,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatchServiceInterface $dispatchService
    ) {
    }

    public function createPredefinedProperty(): PredefinedProperty
    {
        return $this->propertyHydrator->hydratePredefinedProperty(
            $this->repository->createPredefinedProperty()
        );
    }

    /**
     * @return array<int, PredefinedProperty>
     */
    public function getPredefinedProperties(PropertiesParameters $parameters): array
    {
        $properties = $this->repository->listProperties($parameters);
        $hydratedProperties = [];
        foreach ($properties->load() as $property) {

            $predefinedProperty = $this->propertyHydrator->hydratePredefinedProperty($property);

            $this->dispatchService->dispatch($predefinedProperty);

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

            $this->dispatchService->dispatch($elementProperty);

            $hydratedProperties[] = $elementProperty;
        }

        return $hydratedProperties;
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): PredefinedProperty
    {

        return $this->propertyHydrator->hydratePredefinedProperty(

            $this->repository->updatePredefinedProperty($id, $property)
        );
    }

    public function updateElementProperties(string $elementType, int $id, UpdateElementProperties $items): void
    {
        $this->repository->updateElementProperties($elementType, $id, $items);
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function deletePredefinedProperty(string $id): void
    {
        $this->repository->deletePredefinedProperty($id);
    }
}
