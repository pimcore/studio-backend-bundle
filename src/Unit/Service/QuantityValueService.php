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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\QuantityValue\UnitResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Unit\Event\PreResponse\QuantityValueConversionEvent;
use Pimcore\Bundle\StudioBackendBundle\Unit\Event\PreResponse\QuantityValueUnitEvent;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertAllUnitsParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertUnitParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\Repository\QuantityValueRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValue;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValues;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class QuantityValueService implements QuantityValueServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private QuantityValueRepositoryInterface $quantityValueRepository,
        private UnitConversionService $unitConversionService,
        private UnitResolverInterface $unitResolver,
    ) {
    }

    /**
     * @return QuantityValueUnit[]
     */
    public function listUnits(): array
    {
        $listing = $this->quantityValueRepository->getUnitList();
        $units = [];

        foreach ($listing as $unit) {
            $quantityValueUnit = new QuantityValueUnit(
                $unit->getId(),
                $unit->getAbbreviation(),
                $unit->getGroup(),
                $unit->getLongname(),
                $unit->getBaseunit() ? $unit->getBaseunit()->getId() : null,
                $unit->getReference(),
                $unit->getFactor(),
                $unit->getConversionOffset(),
                $unit->getConverter()
            );

            $this->eventDispatcher->dispatch(
                new QuantityValueUnitEvent($quantityValueUnit),
                QuantityValueUnitEvent::EVENT_NAME
            );

            $units[] = $quantityValueUnit;
        }

        return $units;
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function convertUnit(ConvertUnitParameter $parameters): float|int
    {
        return $this->getConvertedValue(
            $this->getUnit($parameters->getFromUnitId()),
            $this->getUnit($parameters->getToUnitId()),
            $parameters->getValue()
        );
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function convertAllUnits(ConvertAllUnitsParameter $parameters): ConvertedQuantityValues
    {
        $fromUnit = $this->getUnit($parameters->getFromUnitId());
        $baseUnit = $fromUnit->getBaseunit() ?? $fromUnit;
        $toUnits = $this->quantityValueRepository->getUnitListByBaseUnit($baseUnit->getId(), $fromUnit->getId());

        $convertedValues = [];
        foreach ($toUnits as $toUnit) {
            $convertedValue = $this->getConvertedValue($fromUnit, $toUnit, $parameters->getValue());
            $convertedValues[] = new ConvertedQuantityValue(
                $toUnit->getAbbreviation(),
                $toUnit->getLongname(),
                round($convertedValue, 4)
            );
        }

        $collection = new ConvertedQuantityValues(
            $parameters->getValue(),
            $parameters->getFromUnitId(),
            $convertedValues
        );

        $this->eventDispatcher->dispatch(
            new QuantityValueConversionEvent($collection),
            QuantityValueConversionEvent::EVENT_NAME
        );

        return $collection;
    }

    /**
     * @throws NotFoundException
     */
    private function getUnit(string $unitId): Unit
    {
        $unit = $this->unitResolver->getById($unitId);

        if ($unit === null) {
            throw new NotFoundException('Unit', $unitId);
        }

        return $unit;
    }

    /**
     * @throws DatabaseException
     */
    private function getConvertedValue(Unit $fromUnit, Unit $toUnit, float|int $value): float|int
    {
        try {
            $convertedValue = $this->unitConversionService->convert(
                new QuantityValue($value, $fromUnit),
                $toUnit
            );
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf('Could not convert unit "%s" to "%s": %s', $fromUnit, $toUnit, $exception->getMessage())
            );
        }

        return $convertedValue->getValue();
    }
}
