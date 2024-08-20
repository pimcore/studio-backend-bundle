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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Filter;

use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @internal
 */
final readonly class PaginationFilter implements FilterInterface
{
    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        $pageSizeColumn = $parameters->getFirstColumnFilterByType(FilterType::PAGE_SIZE->value);

        $pageColumn = $parameters->getFirstColumnFilterByType(FilterType::PAGE->value);

        if ($pageSizeColumn === null || $pageColumn === null) {
            return $listing;
        }

        $listing->setLimit((int)$pageSizeColumn->getFilterValue());
        $listing->setOffset(((int)$pageColumn->getFilterValue() - 1) * (int)$pageSizeColumn->getFilterValue());

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }
}
