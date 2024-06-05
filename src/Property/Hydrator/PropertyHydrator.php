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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Models\Predefined\PredefinedResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Extractor\Element\DataExtractorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\ElementProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 */
final readonly class PropertyHydrator implements PropertyHydratorInterface
{
    private const ALLOWED_MODEL_PROPERTIES = [
        'key',
        'filename',
        'path',
        'id',
        'type',
    ];

    private const EXCLUDED_PROPERTIES = [
        'cid',
        'ctype',
        'cpath',
        'dao',
    ];

    public function __construct(
        private PredefinedResolverInterface $predefinedResolver,
        private DataExtractorInterface $dataExtractor
    ) {
    }

    public function hydratePredefinedProperty(Predefined $property): PredefinedProperty
    {
        return new PredefinedProperty(
            $property->getId(),
            $property->getName(),
            $property->getDescription(),
            $property->getKey(),
            $property->getType(),
            $property->getData(),
            $property->getConfig(),
            $property->getCtype(),
            $property->getInheritable(),
            $property->getCreationDate(),
            $property->getModificationDate()
        );
    }

    public function hydrateElementProperty(Property $property): ElementProperty
    {
        $propertyData = $this->extractData($property);

        return new ElementProperty(
            $propertyData['name'],
            $propertyData['modelData'] ?? $propertyData['data'],
            $propertyData['type'],
            $propertyData['inheritable'],
            $propertyData['inherited'],
            $propertyData['config'],
            $propertyData['predefinedName'] ?? 'Custom',
            $propertyData['description']
        );
    }

    private function extractData(Property $property): array
    {
        $data['modelData'] = match (true) {
            $property->getData() instanceof Document ||
            $property->getData() instanceof Asset ||
            $property->getData() instanceof AbstractObject => $this->dataExtractor->extractData($property->getData()),
            default => null,
        };

        return [
            ... $this->excludeProperties($property->getObjectVars()),
            ... $data,
            ... $this->extractPredefinedPropertyData($property),
        ];
    }

    private function extractDataFromModel(Document|Asset|AbstractObject $data): array
    {
        return array_intersect_key($data->getObjectVars(), array_flip(self::ALLOWED_MODEL_PROPERTIES));
    }

    private function excludeProperties(array $values): array
    {
        return array_diff_key($values, array_flip(self::EXCLUDED_PROPERTIES));
    }

    private function extractPredefinedPropertyData(Property $property): array
    {
        $empty = ['config' => null, 'predefinedName' => null, 'description' => null];
        if (!$property->getName() || !$property->getType()) {
            return $empty;
        }

        $predefinedProperty = $this->predefinedResolver->getByKey($property->getName());
        if (!$predefinedProperty || $predefinedProperty->getType() !== $property->getType()) {
            return $empty;
        }

        return [
            'config' => $predefinedProperty->getConfig(),
            'predefinedName' => $predefinedProperty->getName(),
            'description' => $predefinedProperty->getDescription(),
        ];
    }
}
