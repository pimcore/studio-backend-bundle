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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Extractor;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined;

/**
 * @internal
 */
final class PropertyDataExtractor implements PropertyDataExtractorInterface
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

    public function extractData(Property $property): array
    {
        $data['modelData'] = match (true) {
            $property->getData() instanceof Document ||
            $property->getData() instanceof Asset ||
            $property->getData() instanceof AbstractObject => $this->extractDataFromModel($property->getData()),
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

        $predefinedProperty = Predefined::getByKey($property->getName());
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
