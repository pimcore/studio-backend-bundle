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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValue;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;
use Pimcore\Model\DataObject\QuantityValue\Unit;

/**
 * @internal
 */
final readonly class QuantityValueHydrator implements QuantityValueHydratorInterface
{
    public function hydrateUnit(Unit $unit): QuantityValueUnit
    {
        return new QuantityValueUnit(
            $unit->getId(),
            $unit->getAbbreviation(),
            $unit->getGroup(),
            $unit->getLongname(),
            $unit->getBaseunit() ? $unit->getBaseunit()->getId() : null,
            $unit->getReference(),
            $unit->getFactor(),
            $unit->getConversionOffset(),
            $unit->getConverter(),
        );
    }

    public function hydrateConvertedValue(
        string $unitAbbreviation,
        string $unitLongName,
        float $convertedValue,
    ): ConvertedQuantityValue {
        return new ConvertedQuantityValue(
            $unitAbbreviation,
            $unitLongName,
            $convertedValue,
        );
    }
}
