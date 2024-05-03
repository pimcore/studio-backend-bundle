<?php

namespace Pimcore\Bundle\StudioBackendBundle\Property\Service;

use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;

interface PropertyHydratorServiceInterface
{
    /**
     * @return PredefinedProperty[]
     */
    public function getHydratedProperties(PropertiesParameters $parameters): array;

    public function getHydratedPropertyForElement(string $elementType, int $id): array;
}