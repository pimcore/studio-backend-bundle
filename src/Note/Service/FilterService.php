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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Note\MappedParameter\NoteParameters;
use Pimcore\Model\Element\Note\Listing as NoteListing;

/**
 * @internal
 */
final readonly class FilterService implements FilterServiceInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    public function applyFilter(NoteListing $list, NoteParameters $parameters): void
    {
        if ($parameters->getFilter()) {
            $list->addConditionParam(
                $this->createFilterCondition(),
                ['filter' => '%' . $parameters->getFilter() . '%']
            );
        }
    }

    /**
     * @throws InvalidFilterException
     */
    public function applyFieldFilters(NoteListing $list, NoteParameters $parameters): void
    {
        try {
            if (empty($parameters->getFieldFiltersArray())) {
                return;
            }

            $propertyKey = 'field';

            foreach ($parameters->getFieldFiltersArray() as $filter) {
                $operator = $this->findOperator($filter['type'], $filter['operator']);
                $value = $this->prepareValue($filter['type'], $filter['operator'], $filter['value']);

                if ($operator === 'LIKE') {
                    $value = '%' . $value . '%';
                }

                if ($filter[$propertyKey] === 'user') {
                    $list->addConditionParam(
                        '`user` IN (SELECT `id` FROM `users` WHERE `name` ' . $operator . ' :user)',
                        ['user' => $value]
                    );
                }

                $quotedField = $this->dbResolver->get()->quoteIdentifier($filter[$propertyKey]);
                if ($filter['type'] === 'date' && $filter['operator'] === 'eq') {
                    $maxTime = $value + (86400 - 1);
                    $dateCondition = $quotedField . ' BETWEEN :minTime AND :maxTime';
                    $list->addConditionParam($dateCondition, ['minTime' => $value, 'maxTime' => $maxTime]);
                } else {
                    $list->addConditionParam(
                        $quotedField . ' ' . $operator . ' :' . $filter[$propertyKey],
                        [$filter[$propertyKey] => $value]
                    );
                }
            }
        } catch (Exception) {
            throw new InvalidFilterException('fieldFilters');
        }
    }

    public function applyElementFilter(NoteListing $list, NoteElementParameters $noteElement): void
    {
        if ($noteElement->getId() && $noteElement->getType()) {
            $list->addConditionParam(
                '(cid = :id AND ctype = :type)',
                ['id' => $noteElement->getId(), 'type' => $noteElement->getType()]
            );
        }
    }

    private function prepareValue(string $type, string $operator, mixed $value): mixed
    {
        return match ($type) {
            'date' => strtotime($value),
            default => $this->matchValueOperator($operator, $value)
        };
    }

    private function matchValueOperator(string $operator, mixed $value): mixed
    {
        return match ($operator) {
            'boolean' => (int)$value,
            default => $value
        };
    }

    private function createFilterCondition(): string
    {
        return '('
            . '`title` LIKE :filter'
            . ' OR `description` LIKE :filter'
            . ' OR `type` LIKE :filter'
            . ' OR `user` IN (SELECT `id` FROM `users` WHERE `name` LIKE :filter)'
            . " OR DATE_FORMAT(FROM_UNIXTIME(`date`), '%Y-%m-%d') LIKE :filter"
            . ')';
    }

    private function findOperator(string $type, string $operator): string
    {
        return match ($type) {
            'string' => 'LIKE',
            'numeric', 'date' => $this->matchNumericOperator($operator),
            default => '='
        };
    }

    private function matchNumericOperator(string $operator): string
    {
        return match ($operator) {
            'lt' => '<',
            'lte' => '<=',
            'gt' => '>',
            'gte' => '>=',
            default => '='
        };
    }
}
