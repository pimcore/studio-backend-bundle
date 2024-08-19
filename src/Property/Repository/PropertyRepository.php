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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Property\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertiesListing;

/**
 * @internal
 */
final readonly class PropertyRepository implements PropertyRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ListingFilterInterface $filterService,
        private PredefinedResolverInterface $predefinedResolver,
    ) {
    }

    /**
     * @throws NotWriteableException
     */
    public function createPredefinedProperty(): Predefined
    {
        if (!(new Predefined())->isWriteable()) {
            throw new NotWriteableException('Predefined Property');
        }

        $property = $this->predefinedResolver->create();
        $property->setCtype(ElementTypes::TYPE_DOCUMENT);
        $property->setName('New Property');
        $property->setKey('new_key');
        $property->setType('text');
        $property->save();

        return $property;
    }

    /**
     * @throws NotFoundException
     */
    public function getPredefinedProperty(string $id): Predefined
    {
        $predefined = $this->predefinedResolver->getById($id);
        if (!$predefined) {
            throw new NotFoundException('Property', $id);
        }

        return $predefined;
    }

    public function listProperties(FilterParameter $parameters): PropertiesListing
    {
        $listing = new PropertiesListing();

        return $this->filterService->applyFilters(
            $parameters,
            $listing
        );
    }

    public function updateElementProperties(ElementInterface $element, array $data): void
    {
        $properties = [];
        foreach ($data['properties'] as $propertyData) {
            $property = new Property();
            $property->setType($propertyData['type']);
            $property->setName($propertyData['key']);
            $property->setData($propertyData['data']);
            $property->setInheritable($propertyData['inheritable']);
            $properties[$propertyData['key']] = $property;
        }
        $element->setProperties($properties);
    }

    /**
     * @throws NotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): void
    {
        $predefined = $this->getPredefinedProperty($id);

        $predefined->setName($property->getName());
        $predefined->setDescription($property->getDescription());
        $predefined->setKey($property->getKey());
        $predefined->setType($property->getType());
        $predefined->setData($property->getData());
        $predefined->setConfig($property->getConfig());
        $predefined->setCtype($property->getCtype());
        $predefined->setInheritable($property->isInheritable());

        $predefined->save();
    }

    /**
     * @throws NotFoundException
     */
    public function deletePredefinedProperty(string $id): void
    {
        $predefined = $this->getPredefinedProperty($id);

        $predefined->delete();
    }
}
