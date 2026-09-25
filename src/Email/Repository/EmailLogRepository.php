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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Repository;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\EmailLogResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Model\Tool\Email\Log\Listing;
use function in_array;

/**
 * @internal
 */
final readonly class EmailLogRepository implements EmailLogRepositoryInterface
{
    private const DEFAULT_ORDER_KEY = 'sentDate';

    private const array SORTABLE_KEYS = ['id', 'sentDate', 'from', 'to', 'subject'];

    public function __construct(
        private EmailLogResolverInterface $emailLogResolver,
    ) {
    }

    public function getListing(
        CollectionParameters $parameters,
        ?string $email = null,
    ): Listing {
        $limit = $parameters->getPageSize();
        $listing = new Listing();
        $listing->setLimit($limit);
        $listing->setOffset(($parameters->getPage() - 1) * $limit);
        $listing->setOrderKey(self::DEFAULT_ORDER_KEY);
        $listing->setOrder('DESC');

        return $listing;
    }

    /**
     * Filters for firstname, lastname, email, id globally in all E-Mail Log entries.
     */
    public function getFilteredListing(FilterParameter $filter): Listing
    {
        $listing = new Listing();

        $idFilter = $filter->getSimpleColumnFilterByType('id');
        if ($idFilter !== null) {
            $listing->setCondition('id = :id', ['id' => (int)$idFilter->getFilterValue()]);

            return $this->applySearchOptions($listing, $filter);
        }

        $filterTerm = $this->buildFilterTerm($filter);
        if ($filterTerm !== null) {
            $condition =
                'MATCH (`from`,`to`,`cc`,`bcc`,`subject`,`params`)
                AGAINST (' . $listing->quote($filterTerm) . ' IN BOOLEAN MODE)';
            $listing->setCondition($condition);
        }

        return $this->applySearchOptions($listing, $filter);
    }

    private function buildFilterTerm(FilterParameter $filter): ?string
    {
        $searchTerms = [];

        $textFilterTypes = ['firstname', 'lastname', 'email'];
        foreach ($textFilterTypes as $filterType) {
            $textFilter = $filter->getSimpleColumnFilterByType($filterType);
            if ($textFilter === null) {
                continue;
            }

            $value = trim((string)$textFilter->getFilterValue());
            if ($value === '') {
                continue;
            }

            $value = str_replace('%', '*', $value);
            $value = htmlspecialchars($value, ENT_QUOTES);

            if (str_contains($value, '@')) {
                $parts = explode(' ', $value);
                $parts = array_map(
                    static fn (string $part): string => str_contains($part, '@') ? '"' . $part . '"' : $part,
                    $parts
                );
                $value = implode(' ', $parts);
            }

            if (str_starts_with($value, '@')) {
                $value = substr($value, 1);
            }

            $searchTerms[] = $value;
        }

        if ($searchTerms === []) {
            return null;
        }

        return implode(' ', $searchTerms);
    }

    private function applySearchOptions(Listing $listing, FilterParameter $filter): Listing
    {
        $listing->setLimit($filter->getPageSize());
        $listing->setOffset($filter->getStart());

        $sortFilter = $filter->getSortFilter();
        if (
            $sortFilter->getKey()
            && in_array($sortFilter->getKey(), self::SORTABLE_KEYS, true)
            && $sortFilter->getDirection()
        ) {
            $listing->setOrderKey($sortFilter->getKey());
            $listing->setOrder(
                strtolower($sortFilter->getDirection()) === SortDirection::DESC->value
                    ? SortDirection::DESC->value
                    : SortDirection::ASC->value
            );
        } else {
            $listing->setOrderKey(self::DEFAULT_ORDER_KEY);
            $listing->setOrder('DESC');
        }

        return $listing;
    }

    /**
     * @throws NotFoundException
     */
    public function deleteEntry(int $id): void
    {
        $blockList = $this->getExistingEntry($id);
        $blockList->delete();
    }

    /**
     * @throws NotFoundException
     */
    public function getExistingEntry(int $id): Log
    {
        $entry = $this->getEntry($id);

        if ($entry === null) {
            throw new NotFoundException(
                type: 'E-mail log entry',
                id: $id
            );
        }

        return $entry;
    }

    private function getEntry(int $id): ?Log
    {
        return $this->emailLogResolver->getById($id);
    }
}
