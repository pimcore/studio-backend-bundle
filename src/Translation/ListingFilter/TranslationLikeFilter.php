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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\ListingFilter;

use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @internal
 */
final readonly class TranslationLikeFilter implements FilterInterface
{
    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        $equalsColumn = $parameters->getFirstColumnFilterByType(FilterType::TRANSLATION_LIKE->value);

        if ($equalsColumn === null) {
            return $listing;
        }

        $listing->addConditionParam(
            // Use the 'text' field for language like filtering
            // This is necessary because language fields are joined together
            $equalsColumn->getKey() .'.text' . ' LIKE :' . $equalsColumn->getKey(),
            [$equalsColumn->getKey() => "%{$equalsColumn->getFilterValue()}%"]
        );

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof AbstractListing;
    }
}
