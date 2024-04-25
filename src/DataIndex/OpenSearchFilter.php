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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Filters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Request\CollectionParametersInterface;

/**
 * @internal
 */
final readonly class OpenSearchFilter implements FilterServiceInterface, OpenSearchFilterInterface
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
    public function applyFilters(CollectionParametersInterface $parameters, string $type): QueryInterface
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
            OpenSearchFilterInterface::TYPE_ASSET => $filters->getAssetFilters(),
            OpenSearchFilterInterface::TYPE_DATA_OBJECT => $filters->getDataObjectFilters(),
            OpenSearchFilterInterface::TYPE_DOCUMENT => $filters->getDocumentFilters(),
            default => throw new InvalidFilterTypeException(400, "Unknown filter type: $type")
        };
    }

    public function getType(): string
    {
        return OpenSearchFilterInterface::SERVICE_TYPE;
    }
}
