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
    private const SUPPORTED_LISTINGS = [PropertyListing::class];

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$listing instanceof PropertyListing) {
            return $listing;
        }

        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        /** @var ?ColumnFilter $name */
        $name = $parameters->getFirstColumnFilterByType(FilterType::PROPERTY_NAME->value);

        /** @var ?ColumnFilter $type */
        $type = $parameters->getFirstColumnFilterByType(FilterType::PROPERTY_ELEMENT_TYPE->value);

        $translator = $this->translator;

        $listing->setFilter(static function (Predefined $predefined) use ($type, $name, $translator) {

            if (
                $type &&
                $type->getFilterValue() &&
                !str_contains($predefined->getCtype(), $type->getFilterValue())) {
                return false;
            }

            if (
                $name &&
                $name->getFilterValue() &&
                stripos($translator->trans($predefined->getName(), [], 'admin'), $name->getFilterValue()) === false
            ) {
                return false;
            }

            return true;
        });

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return in_array(get_class($listing), self::SUPPORTED_LISTINGS, true);
    }
}
