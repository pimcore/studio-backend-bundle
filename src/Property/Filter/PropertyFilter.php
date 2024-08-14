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

use Pimcore\Bundle\StudioBackendBundle\Element\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertyListing;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PropertyFilter implements FilterInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function apply(
        mixed $parameters, 
        AbstractListing|CallableFilterListingInterface $listing
    ): AbstractListing|CallableFilterListingInterface
    {
        if (!$listing instanceof PropertyListing) {
            return $listing;
        }

        if(!$parameters instanceof FilterParameter) {
            return $listing;
        }

        $nameFilter = null;
        foreach ($parameters->getColumnFilterByType(ColumnType::PROPERTY_NAME->value) as $column) {
            $nameFilter = $column->getFilterValue();
            break;
        }

        $typeFilter = null;
        foreach ($parameters->getColumnFilterByType(ColumnType::PROPERTY_ELEMENT_TYPE->value) as $column) {
            $typeFilter = $column->getFilterValue();
            break;
        }

        $translator = $this->translator;

        $listing->setFilter(static function (Predefined $predefined) use ($typeFilter, $nameFilter, $translator) {

            if ($typeFilter && !str_contains($predefined->getCtype(), $typeFilter)) {
                return false;
            }
            if ($nameFilter && stripos($translator->trans($predefined->getName(), [], 'admin'), $nameFilter) === false) {
                return false;
            }

            return true;
        });
        
        return $listing;
    }
}