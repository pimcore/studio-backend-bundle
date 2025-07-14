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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Filter;

use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertyListing;
use Symfony\Contracts\Translation\TranslatorInterface;
use function get_class;
use function in_array;

final readonly class PropertyFilter implements FilterInterface
{
    private const array SUPPORTED_LISTINGS = [PropertyListing::class];

    private const array SUPPORTED_FILTER_KEYS = ['name', 'description', 'key', 'type', 'data', 'config'];

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$listing instanceof PropertyListing || !$parameters instanceof FilterParameter) {
            return $listing;
        }

        /** @var ?ColumnFilter $filter */
        $filter = $parameters->getFirstColumnFilterByType(FilterType::PROPERTY_FILTER->value);

        /** @var ?ColumnFilter $type */
        $type = $parameters->getFirstColumnFilterByType(FilterType::PROPERTY_ELEMENT_TYPE->value);

        $listing->setFilter(fn(Predefined $predefined) => $this->shouldInclude($predefined, $filter, $type));

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return in_array(get_class($listing), self::SUPPORTED_LISTINGS, true);
    }

    private function shouldInclude(Predefined $predefined, ?ColumnFilter $filter, ?ColumnFilter $type): bool
    {
        $typeValue = $type?->getFilterValue();
        if ($typeValue && !str_contains($predefined->getCtype(), $typeValue)) {
            return false;
        }

        $filterValue = $filter?->getFilterValue();
        if (!$filterValue) {
            return true;
        }

        foreach ($predefined->getObjectVars() as $key => $value) {
            if ($value && in_array($key, self::SUPPORTED_FILTER_KEYS, true)) {
                $translated = $key === 'name' ? $this->translator->trans($value, [], 'admin') : $value;
                if (stripos($translated, $filterValue) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
