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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Unit\Event\PreResponse\QuantityValueUnitEvent;
use Pimcore\Bundle\StudioBackendBundle\Unit\Hydrator\QuantityValueHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\CreateUnitParameters;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\UnitParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\UpdateUnitParameters;
use Pimcore\Bundle\StudioBackendBundle\Unit\Repository\QuantityValueRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;
use Pimcore\Model\DataObject\QuantityValue\Service as QuantityValueModelService;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use function mb_strlen;
use function sprintf;

/**
 * @internal
 */
final readonly class QuantityValueService implements QuantityValueServiceInterface
{
    private const int MAX_UNIT_ID_LENGTH = 50;

    public function __construct(
        private DownloadServiceInterface $downloadService,
        private EventDispatcherInterface $eventDispatcher,
        private FilterMapperServiceInterface $filterMapper,
        private QuantityValueHydratorInterface $hydrator,
        private QuantityValueModelService $quantityValueModelService,
        private QuantityValueRepositoryInterface $quantityValueRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listUnits(): array
    {
        $listing = $this->quantityValueRepository->getUnitList();
        $units = [];

        foreach ($listing as $unit) {
            $units[] = $this->getHydratedUnit($unit);
        }

        return $units;
    }

    /**
     * {@inheritdoc}
     */
    public function listUnitCollection(CollectionFilterParameter $parameters): Collection
    {
        $listing = $this->quantityValueRepository->getUnitListing(
            $this->filterMapper->getFilterParameters($parameters)
        );
        $units = $listing->getUnits();
        $list = [];

        foreach ($units as $unit) {
            $list[] = $this->getHydratedUnit($unit);
        }

        return new Collection(
            $listing->getTotalCount(),
            $list
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createUnit(CreateUnitParameters $parameters): QuantityValueUnit
    {
        $id = $parameters->getId();

        if (mb_strlen($id) > self::MAX_UNIT_ID_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unit ID must not exceed %d characters, provided ID has %d characters.',
                    self::MAX_UNIT_ID_LENGTH,
                    mb_strlen($id)
                )
            );
        }

        if ($this->quantityValueRepository->unitExists($id)) {
            throw new InvalidArgumentException(
                sprintf('Unit with ID "%s" already exists.', $id)
            );
        }

        $unit = new Unit();
        $unit->setId($id);
        $this->applyUnitParameters($unit, $parameters);

        try {
            $unit->save();
        } catch (Exception $e) {
            throw new EnvironmentException(
                sprintf('Failed to create unit: %s', $e->getMessage())
            );
        }

        return $this->getHydratedUnit($unit);
    }

    /**
     * {@inheritdoc}
     */
    public function updateUnit(string $id, UpdateUnitParameters $parameters): QuantityValueUnit
    {
        $unit = $this->getUnit($id);

        $this->applyUnitParameters($unit, $parameters);

        try {
            $unit->save();
        } catch (Exception $e) {
            throw new EnvironmentException(
                sprintf('Failed to update unit: %s', $e->getMessage())
            );
        }

        return $this->getHydratedUnit($unit);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUnit(string $id): void
    {
        $unit = $this->getUnit($id);

        try {
            $unit->delete();
        } catch (Exception $e) {
            throw new EnvironmentException(
                sprintf('Failed to delete unit: %s', $e->getMessage())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function importUnits(string $json): void
    {
        $success = $this->quantityValueModelService->importDefinitionFromJson($json);

        if (!$success) {
            throw new EnvironmentException('Failed to import quantity value unit definitions.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exportUnits(): Response
    {
        $json = $this->quantityValueModelService->generateDefinitionJson();

        if ($json === false) {
            throw new EnvironmentException('Failed to export quantity value unit definitions.');
        }

        return $this->downloadService->downloadJSON($json, 'quantityvalue_unit_export.json');
    }

    /**
     * @throws NotFoundException
     */
    private function getUnit(string $id): Unit
    {
        $unit = $this->quantityValueRepository->getUnitById($id);

        if ($unit === null) {
            throw new NotFoundException('Unit', $id);
        }

        return $unit;
    }

    private function getHydratedUnit(Unit $unit): QuantityValueUnit
    {
        $hydrated = $this->hydrator->hydrateUnit($unit);

        $this->eventDispatcher->dispatch(
            new QuantityValueUnitEvent($hydrated),
            QuantityValueUnitEvent::EVENT_NAME
        );

        return $hydrated;
    }

    private function applyUnitParameters(Unit $unit, UnitParametersInterface $parameters): void
    {
        $unit->setAbbreviation($parameters->getAbbreviation());
        $unit->setLongname($parameters->getLongname());
        $unit->setGroup($parameters->getGroup());
        $unit->setReference($parameters->getReference());
        $unit->setFactor($parameters->getFactor());
        $unit->setConversionOffset($parameters->getConversionOffset());
        $unit->setConverter($parameters->getConverter());

        $baseunit = $parameters->getBaseunit();
        if ($baseunit === '-1') {
            $baseunit = null;
        }
        $unit->setBaseunit($baseunit);
    }
}
