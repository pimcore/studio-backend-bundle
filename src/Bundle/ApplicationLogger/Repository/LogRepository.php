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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Repository;

use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Util\Constant\FilterableFields;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use function count;
use function in_array;
use function is_int;

/**
 * @internal
 */
final class LogRepository implements LogRepositoryInterface
{
    use ElementProviderTrait;

    private array $allowedKeys = [];

    public function __construct(
        private readonly DbResolverInterface $dbResolver,
    ) {

    }

    /**
     * {@inheritdoc}
     */
    public function list(CollectionFilterParameter $parameters): array
    {
        $qb = $this->dbResolver->get()->createQueryBuilder();
        $qb
            ->select('*, priority + 0 AS priority_value')
            ->from(ApplicationLoggerDb::TABLE_NAME);

        $filters = $parameters->getFilters();
        if ($filters === null) {
            $qb->orderBy('id', 'DESC');

            return $this->performSearch($qb);
        }

        $this->addKeyFilters($qb, $filters, FilterType::LIKE->value);
        $this->addKeyFilters($qb, $filters, FilterType::EQUALS->value);
        $this->addDateFilters($qb, $filters);
        $this->addSorting($qb, $filters->getSortFilter());
        $this->addPaging($qb, $filters);

        return $this->performSearch($qb);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount(): int
    {
        $qb = $this->dbResolver->get()->createQueryBuilder();
        $qb
            ->select('COUNT(*) AS total_count')
            ->from(ApplicationLoggerDb::TABLE_NAME);

        try {
            $result = $qb->executeQuery()->fetchOne();

            return (int) $result;
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getComponents(): array
    {
        $qb = $this->dbResolver->get()->createQueryBuilder();
        $qb
            ->select('component')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->where($qb->expr()->isNotNull('component'))
            ->groupBy('component');

        try {

            return $qb->executeQuery()->fetchFirstColumn();
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e);
        }
    }

    private function addKeyFilters(QueryBuilder $queryBuilder, FilterParameter $parameters, string $operation): void
    {
        $filters =  iterator_to_array($parameters->getColumnFilterByType($operation));
        if (count($filters) === 0) {
            return;
        }

        $operator = 'LIKE';
        if ($operation === FilterType::EQUALS->value) {
            $operator = '=';
        }

        /** @var ColumnFilter $filter */
        foreach ($filters as $filter) {
            $key = $filter->getKey();
            if (!$this->isKeyAllowed($key)) {
                continue;
            }

            $paramName = $key . '_' . $operation;
            $paramValue = $filter->getFilterValue();
            $paramType = is_int($filter->getFilterValue()) ? Types::INTEGER : Types::STRING;
            if ($operation === FilterType::LIKE->value) {
                $paramType = Types::STRING;
                $paramValue = '%' . $paramValue . '%';
            }

            $queryBuilder
                ->andWhere($key . ' ' . $operator . ' :' . $paramName)
                ->setParameter($paramName, $paramValue, $paramType);
        }
    }

    private function addDateFilters(QueryBuilder $queryBuilder, FilterParameter $parameters): void
    {
        $filters =  iterator_to_array($parameters->getColumnFilterByType(FilterType::DATE->value));
        if (count($filters) === 0) {
            return;
        }

        /** @var ColumnFilter $filter */
        foreach ($filters as $filter) {
            $operation = $filter->getFilterValue()['operator'] ?? '';
            $operator = $this->getDateOperation($operation);
            if ($operator === null) {
                continue;
            }

            $queryBuilder->andWhere('timestamp ' . $operator . ' :' . $operation . '_date');
            $queryBuilder->setParameter(
                $operation . '_date',
                Carbon::parse($filter->getFilterValue()['value'])->setTimezone('UTC'),
                Types::DATETIME_MUTABLE
            );
        }
    }

    private function getDateOperation(string $operation): ?string
    {
        return match ($operation) {
            'to' => '<=',
            'from' => '>',
            default => null
        };
    }

    private function addPaging(QueryBuilder $queryBuilder, FilterParameter $parameters): void
    {
        $page = $parameters->getPage();
        $queryBuilder
            ->setFirstResult(($page - 1) * $parameters->getPageSize())
            ->setMaxResults($parameters->getPageSize());
    }

    private function addSorting(QueryBuilder $queryBuilder, SortFilter $sortFilter): void
    {
        $queryBuilder
            ->orderBy($sortFilter->getKey(), $sortFilter->getDirection());
    }

    /**
     * @throws DatabaseException
     */
    private function performSearch(QueryBuilder $queryBuilder): array
    {
        try {
            return $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e);
        }

    }

    private function isKeyAllowed(string $key): bool
    {
        if (empty($this->allowedKeys)) {
            $this->allowedKeys = FilterableFields::values();
        }

        return in_array($key, $this->allowedKeys, true);
    }
}
