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

use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Model\Translation\Listing;
use function in_array;
use function iterator_to_array;
use function sprintf;

/**
 * @internal
 */
final readonly class TranslationLikeFilter implements FilterInterface
{
    private const string VALUE_PARAMETER = 'translationLikeValue';

    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        if (!$parameters instanceof FilterParameter || !$listing instanceof Listing) {
            return $listing;
        }

        $columnFilters = iterator_to_array(
            $parameters->getColumnFilterByType(FilterType::TRANSLATION_LIKE->value),
            false
        );

        $languages = $listing->getLanguages() ?? [];
        foreach ($columnFilters as $columnFilter) {
            if (!in_array($columnFilter->getKey(), $languages, true)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid translation language "%s"', $columnFilter->getKey())
                );
            }
        }

        $db = $this->dbResolver->get();
        foreach ($columnFilters as $index => $columnFilter) {
            $parameterName = self::VALUE_PARAMETER . $index;
            $listing->addConditionParam(
                // Use the 'text' field for language like filtering
                // This is necessary because language fields are joined together
                $db->quoteIdentifier($columnFilter->getKey()) . '.text LIKE :' . $parameterName,
                [$parameterName => "%{$columnFilter->getFilterValue()}%"]
            );
        }

        return $listing;
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof Listing;
    }
}
