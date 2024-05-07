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
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\ElementPropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PredefinedPropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Property\Predefined;

final readonly class PropertyHydratorService implements PropertyHydratorServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface $repository,
        private ServiceResolver $serviceResolver,
        private ElementPropertyHydratorInterface $dataPropertyHydrator,
        private PredefinedPropertyHydratorInterface $predefinedPropertyHydrator,
    ) {
    }

    /**
     * @return PredefinedProperty[]
     */
    public function getHydratedProperties(PropertiesParameters $parameters): array
    {
        $properties = $this->repository->listProperties($parameters);
        $hydratedProperties = [];
        foreach ($properties->load() as $property) {
            $hydratedProperties[] = $this->predefinedPropertyHydrator->hydrate($property);
        }

        return $hydratedProperties;
    }

    public function getHydratedPropertyForElement(string $elementType, int $id): array
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        $hydratedProperties = [];

        foreach($element->getProperties() as $property) {
            $hydratedProperties[] = $this->dataPropertyHydrator->hydrate($property);
        }

        return ['items' => $hydratedProperties];
    }

    public function getHydratedPredefinedProperty(Predefined $predefined): PredefinedProperty
    {
        return $this->predefinedPropertyHydrator->hydrate($predefined);
    }
}
