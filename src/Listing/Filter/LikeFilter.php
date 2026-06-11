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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Filter;

use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @internal
 */
final readonly class LikeFilter implements FilterInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        $likeColumns = iterator_to_array($parameters->getColumnFilterByType(FilterType::LIKE->value));

        if (empty($likeColumns)) {
            return $listing;
        }

        foreach ($likeColumns as $likeColumn) {
            $columnName = $likeColumn->getKey();
            $listing->addConditionParam(
                $this->dbResolver->get()->quoteIdentifier($columnName) . ' LIKE :' . $columnName,
                [$columnName => "%{$likeColumn->getFilterValue()}%"]
            );
        }

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }
}
