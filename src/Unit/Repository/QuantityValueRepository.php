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
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
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
        $this->applySearchCondition($listing, $parameters);
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    private function getUnfilteredUnitListing(): Listing
    {
        return new Listing();
    }

    private function applySearchCondition(Listing $listing, FilterParameter $parameters): void
    {
        $searchFilter = $parameters->getSimpleColumnFilterByType(FilterType::SEARCH->value);
        if (!$searchFilter) {
            return;
        }

        if ($searchFilter->getFilterValue() === '' || $searchFilter->getFilterValue() === null) {
            return;
        }

        $param = ['searchTerm' => "%{$searchFilter->getFilterValue()}%"];
        $listing->addConditionParam('`id` LIKE :searchTerm', $param);
        $listing->addConditionParam('`abbreviation` LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('`longname` LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('`group` LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('`baseunit` LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('`reference` LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('`converter` LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('CAST(`factor` AS CHAR) LIKE :searchTerm', $param, 'OR');
        $listing->addConditionParam('CAST(`conversionOffset` AS CHAR) LIKE :searchTerm', $param, 'OR');
    }
}
