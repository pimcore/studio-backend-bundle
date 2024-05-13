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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\PropertyNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\ElementPropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 */
final readonly class PropertyService implements PropertyServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface       $repository,
        private ServiceResolver           $serviceResolver,
        private PropertyHydratorInterface $propertyHydrator,
    ) {
    }

    public function createPredefinedProperty(): Predefined
    {
        return $this->repository->createPredefinedProperty();
    }

    public function getPredefinedProperties(PropertiesParameters $parameters): array
    {
        $properties = $this->repository->listProperties($parameters);
        $hydratedProperties = [];
        foreach ($properties->load() as $property) {
            $hydratedProperties[] = $this->propertyHydrator->hydratePredefinedProperty($property);
        }

        return $hydratedProperties;
    }

    public function getElementProperties(string $elementType, int $id): array
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        $hydratedProperties = [];

        foreach($element->getProperties() as $property) {
            $hydratedProperties[] = $this->propertyHydrator->hydrateElementProperty($property);
        }

        return $hydratedProperties;
    }

    public function getPredefinedProperty(Predefined $predefined): PredefinedProperty
    {
        return $this->propertyHydrator->hydratePredefinedProperty($predefined);
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): Predefined
    {
        return $this->repository->updatePredefinedProperty($id, $property);
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
