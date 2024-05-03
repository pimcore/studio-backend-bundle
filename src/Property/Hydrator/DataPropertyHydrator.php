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

use Pimcore\Bundle\StudioBackendBundle\Property\Extractor\PropertyDataExtractorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\DataProperty;
use Pimcore\Model\Property;

/**
 * @internal
 */
final readonly class DataPropertyHydrator implements DataPropertyHydratorInterface
{
    public function __construct(
        private PropertyDataExtractorInterface $dataExtractor
    ) {
    }

    public function hydrate(Property $property): DataProperty
    {
        $propertyData = $this->dataExtractor->extractData($property);
        return new DataProperty(
            $propertyData['name'],
            $propertyData['modelData'] ?? $propertyData['data'],
            $propertyData['type'],
            $propertyData['inheritable'],
            $propertyData['inherited'],
            $propertyData['config'],
            $propertyData['predefinedName'],
            $propertyData['description']
        );
    }



}