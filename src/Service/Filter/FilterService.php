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

namespace Pimcore\Bundle\StudioApiBundle\Service\Filter;

use Pimcore\Bundle\StudioApiBundle\Dto\Filter\Parameters;
use Pimcore\Bundle\StudioApiBundle\Exception\InvalidFilterTypeException;
use Pimcore\Bundle\StudioApiBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioApiBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

final readonly class FilterService implements FilterServiceInterface
{
    public function __construct(
        private FilterLoaderInterface $filterLoader,
        private QueryFactoryInterface $queryFactory
    ) {
    }

    /**
     * @throws InvalidQueryTypeException
     * @throws InvalidFilterTypeException
     */
    public function applyCollectionFilter(Parameters $parameters, string $type): QueryInterface
    {
        $query = $this->queryFactory->create($type);
        // apply default filters
        $filters = $this->filterLoader->loadFilters();

        foreach($filters->getFilters() as $filter) {
            $query = $filter->apply($parameters, $query);
        }

        // apply type specific filters

        foreach ($this->getTypeFilters($filters, $type) as $filter) {
            $query = $filter->apply($parameters, $query);
        }

        return $query;
    }

    /**
     * @throws InvalidFilterTypeException
     */
    private function getTypeFilters(Filters $filters, string $type): array
    {
        return match($type) {
            'asset' => $filters->getAssetFilters(),
            'dataObject' => $filters->getDataObjectFilters(),
            'document' => $filters->getDocumentFilters(),
            default => throw new InvalidFilterTypeException("Unknown filter type: $type")
        };
    }
}
