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
use Pimcore\Bundle\StudioBackendBundle\Property\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 */
final readonly class PropertyService implements PropertyServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface $repository,
        private ServiceResolver $serviceResolver,
    ) {
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
        $element = $this->getElement($this->serviceResolver, $elementType, $id);
        $properties = [];
        foreach($items->getProperties() as $updateProperty) {
            $property = new Property();
            $property->setType($updateProperty->getType());
            $property->setName($updateProperty->getKey());
            $property->setData($updateProperty->getData());
            $property->setInheritable($updateProperty->getInheritable());
            $properties[$updateProperty->getKey()] = $property;
        }
        $element->setProperties($properties);
        $element->save();
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function deletePredefinedProperty(string $id): void
    {
        $this->repository->deletePredefinedProperty($id);
    }
}
