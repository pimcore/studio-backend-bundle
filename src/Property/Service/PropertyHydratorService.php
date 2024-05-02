<?php

namespace Pimcore\Bundle\StudioBackendBundle\Property\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\DataPropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PredefinedPropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

final readonly class PropertyHydratorService implements PropertyHydratorServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface $repository,
        private ServiceResolver $serviceResolver,
        private DataPropertyHydratorInterface $dataPropertyHydrator,
        private PredefinedPropertyHydratorInterface $propertyHydrator,
    )
    {
    }

    /**
     * @return PredefinedProperty[]
     */
    public function getHydratedProperties(PropertiesParameters $parameters): array
    {
        $properties = $this->repository->listProperties($parameters);
        $hydratedProperties = [];
        foreach ($properties->load() as $property) {
            $hydratedProperties[] = $this->propertyHydrator->hydrate($property);
        }

        return $hydratedProperties;
    }

    public function getHydratedPropertyForElement(int $id, string $type): array
    {
        $element = $this->getElement($this->serviceResolver, $type, $id);

        foreach($element->getProperties() as $property) {
            $hydratedProperties[] = $this->dataPropertyHydrator->hydrate($property);
        }

        return $hydratedProperties;
    }
}