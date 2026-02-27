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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\QuantityValue\UnitResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\Unit\Listing;

/**
 * @internal
 */
final readonly class QuantityValueRepository implements QuantityValueRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter,
        private UnitResolverInterface $unitResolver,
    ) {
    }

    public function getUnitById(string $id): ?Unit
    {
        return $this->unitResolver->getById($id);
    }

    public function unitExists(string $id): bool
    {
        return $this->getUnitById($id) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitList(): array
    {
        $list = $this->getUnfilteredUnitListing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);

        return $list->getUnits();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitListByBaseUnit(string $baseUnitId, string $fromUnitId): array
    {
        $list = $this->getUnfilteredUnitListing();
        $list->setCondition(
            'baseunit = ' . $list->quote($baseUnitId) .
            ' AND id != ' . $list->quote($fromUnitId)
        );

        return $list->getUnits();
    }

    public function getUnitListing(FilterParameter $parameters): Listing
    {
        $listing = $this->getUnfilteredUnitListing();
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    private function getUnfilteredUnitListing(): Listing
    {
        return new Listing();
    }
}
