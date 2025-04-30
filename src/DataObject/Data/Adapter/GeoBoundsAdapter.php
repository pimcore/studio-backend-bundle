<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DataObject\Coordinates;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class GeoBoundsAdapter implements SetterDataInterface
{
    private const string NORTH_EAST = 'northEast';

    private const string SOUTH_WEST = 'southWest';

    private const array DIRECTIONS = [self::NORTH_EAST, self::SOUTH_WEST];

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?Geobounds {

        $geoBoundsData = $data[$key];
        if ($this->validateBounds($geoBoundsData) === false) {
            return null;
        }

        return new Geobounds(
            new GeoCoordinates(
                $geoBoundsData[self::NORTH_EAST][Coordinates::LATITUDE->value],
                $geoBoundsData[self::NORTH_EAST][Coordinates::LONGITUDE->value]
            ),
            new GeoCoordinates(
                $geoBoundsData[self::SOUTH_WEST][Coordinates::LATITUDE->value],
                $geoBoundsData[self::SOUTH_WEST][Coordinates::LONGITUDE->value]
            )
        );
    }

    private function validateBounds(array $data): bool
    {
        foreach (self::DIRECTIONS as $direction) {
            if (!isset($data[$direction]) || !is_array($data[$direction])) {
                return false;
            }

            foreach (Coordinates::values() as $coordinate) {
                if (empty($data[$direction][$coordinate])) {
                    return false;
                }
            }
        }

        return true;
    }
}
