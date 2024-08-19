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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Filter;

use Pimcore\Bundle\StudioBackendBundle\Filter\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertyListing;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PropertyFilter implements FilterInterface
{
    private const SUPPORTED_LISTINGS = [PropertyListing::class];

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function apply(
        mixed $parameters,
        AbstractListing|CallableFilterListingInterface $listing
    ): AbstractListing|CallableFilterListingInterface {
        if (!$listing instanceof PropertyListing) {
            return $listing;
        }

        if (!$parameters instanceof FilterParameter) {
            return $listing;
        }

        /** @var ?ColumnFilter $name */
        $name = $parameters->getFirstColumnFilterByType(ColumnType::PROPERTY_NAME->value);

        /** @var ?ColumnFilter $type */
        $type = $parameters->getFirstColumnFilterByType(ColumnType::PROPERTY_ELEMENT_TYPE->value);

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
