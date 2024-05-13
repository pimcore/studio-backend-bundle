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

namespace Pimcore\Bundle\StudioBackendBundle\Property;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\PropertyNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertiesListing;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class Repository implements RepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private TranslatorInterface $translator
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

        $property = Predefined::create();
        $property->setCtype(ElementTypes::TYPE_DOCUMENT);
        $property->setName('New Property');
        $property->setKey('new_key');
        $property->setType('text');
        $property->save();

        return $property;
    }

    public function listProperties(PropertiesParameters $parameters): PropertiesListing
    {
        $list = new PropertiesListing();
        $type = $parameters->getElementType();
        $filter = $parameters->getFilter();
        $translator = $this->translator;

        $list->setFilter(static function (Predefined $predefined) use ($type, $filter, $translator) {

            if ($type && !str_contains($predefined->getCtype(), $type)) {
                return false;
            }
            if ($filter && stripos($translator->trans($predefined->getName(), [], 'admin'), $filter) === false) {
                return false;
            }

            return true;
        });

        return $list;
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): Predefined
    {
        $predefined = Predefined::getById($id);

        if (!$predefined) {
            throw new PropertyNotFoundException($id);
        }

        $predefined->setName($property->getName());
        $predefined->setDescription($property->getDescription());
        $predefined->setKey($property->getKey());
        $predefined->setType($property->getType());
        $predefined->setData($property->getData());
        $predefined->setConfig($property->getConfig());
        $predefined->setCtype($property->getCtype());
        $predefined->setInheritable($property->isInheritable());

        $predefined->save();

        return $predefined;
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function deletePredefinedProperty(string $id): void
    {
        $predefined = Predefined::getById($id);

        if (!$predefined) {
            throw new PropertyNotFoundException($id);
        }

        $predefined->delete();
    }

    /**
     * @throws ElementSavingFailedException
     */
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

        try {
            $element->save();
        } catch (DuplicateFullPathException $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
        }
    }
}
