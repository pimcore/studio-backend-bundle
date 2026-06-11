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
final readonly class EqualsFilter implements FilterInterface
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

        $equalsColumns = iterator_to_array($parameters->getColumnFilterByType(FilterType::EQUALS->value));

        if (empty($equalsColumns)) {
            return $listing;
        }

        foreach ($equalsColumns as $equalsColumn) {
            $columnName = $equalsColumn->getKey();
            $listing->addConditionParam(
                $this->dbResolver->get()->quoteIdentifier($columnName) . ' = :' . $columnName,
                [$columnName => $equalsColumn->getFilterValue()]
            );
        }

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }
}
