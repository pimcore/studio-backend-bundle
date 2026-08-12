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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\DraftElementResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Property\Event\ElementPropertyEvent;
use Pimcore\Bundle\StudioBackendBundle\Property\Event\PredefinedPropertyEvent;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\ElementProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
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
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatcherInterface $eventDispatcher,
        private DraftElementResolverInterface $draftElementResolver
    ) {
    }

    /**
     * @throws NotWriteableException
     */
    public function createPredefinedProperty(): PredefinedProperty
    {
        $predefined = $this->propertyRepository->createPredefinedProperty();
        $predefinedProperty = $this->propertyHydrator->hydratePredefinedProperty($predefined);

        $this->eventDispatcher->dispatch(
            new PredefinedPropertyEvent($predefinedProperty),
            PredefinedPropertyEvent::EVENT_NAME
        );

        return $predefinedProperty;
    }

    /**
     * @throws NotFoundException
     */
    public function getPredefinedProperty(string $id): PredefinedProperty
    {
        $predefinedProperty = $this->propertyHydrator->hydratePredefinedProperty(
            $this->propertyRepository->getPredefinedProperty($id)
        );

        $this->eventDispatcher->dispatch(
            new PredefinedPropertyEvent($predefinedProperty),
            PredefinedPropertyEvent::EVENT_NAME
        );

        return $predefinedProperty;
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
     * @throws NotFoundException|ForbiddenException
     *
     * @return array<int, ElementProperty>
     */
    public function getElementProperties(string $elementType, int $id): array
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        $this->securityService->hasElementPermission(
            $element,
            $this->securityService->getCurrentUser(),
            'properties'
        );

        $element = $this->draftElementResolver->resolve($element, $this->securityService->getCurrentUser());
        $hydratedProperties = [];

        foreach ($element->getProperties() as $property) {

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
     * @throws NotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): PredefinedProperty
    {
        $predefined = $this->propertyRepository->updatePredefinedProperty($id, $property);
        $predefinedProperty = $this->propertyHydrator->hydratePredefinedProperty($predefined);

        $this->eventDispatcher->dispatch(
            new PredefinedPropertyEvent($predefinedProperty),
            PredefinedPropertyEvent::EVENT_NAME
        );

        return $predefinedProperty;
    }

    /**
     * @throws NotFoundException
     */
    public function deletePredefinedProperty(string $id): void
    {
        $this->propertyRepository->deletePredefinedProperty($id);
    }
}
