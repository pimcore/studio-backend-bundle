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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Adapter;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Property;

/**
 * @internal
 */
final class PropertiesAdapter implements UpdateAdapterInterface
{
    private const DATA_INDEX = 'properties';

    public function update(ElementInterface $element, array $data): void
    {
        $properties = [];
        foreach ($data[self::DATA_INDEX] as $propertyData) {
            $property = new Property();
            $property->setType($propertyData['type']);
            $property->setName($propertyData['key']);
            $property->setData($propertyData['data']);
            $property->setInheritable($propertyData['inheritable']);
            $properties[$propertyData['key']] = $property;
        }
        $element->setProperties($properties);
    }

    public function getDataIndex(): string
    {
        return self::DATA_INDEX;
    }
}
