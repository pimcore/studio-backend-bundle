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

use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @internal
 */
final readonly class EqualsFilter implements FilterInterface
{
    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        $equalsColumn = $parameters->getFirstColumnFilterByType(FilterType::EQUALS->value);

        if ($equalsColumn === null) {
            return $listing;
        }

        $listing->addConditionParam(
            $equalsColumn->getKey() . ' = :' . $equalsColumn->getKey(),
            [$equalsColumn->getKey() => $equalsColumn->getFilterValue()]
        );

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }
}
