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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\DateFilter as GDIDateFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function is_array;
use function sprintf;

/**
 * @internal
 */
trait DateTimeTrait
{
    private array $filterValue = [];

    public function setFilterValue(array $value): void
    {
        $this->filterValue = $value;
    }

    public function getOnAsCarbon(): Carbon
    {
        if (!isset($this->filterValue['on'])) {
            throw new InvalidArgumentException('Filter value for "on" must be set');
        }

        try {
            return Carbon::parse($this->filterValue['on']);
        } catch (InvalidFormatException $e) {
            throw new InvalidArgumentException(sprintf(
                'Invalid date format for "on": %s, Details: %s',
                $this->filterValue['on'],
                $e->getMessage()
            ));
        }
    }

    public function getFromAsCarbon(): Carbon
    {
        if (!isset($this->filterValue['from'])) {
            throw new InvalidArgumentException('Filter value for "from" must be set');
        }

        try {
            return Carbon::parse($this->filterValue['from']);
        } catch (InvalidFormatException $e) {
            throw new InvalidArgumentException(sprintf(
                'Invalid date format for "from": %s, Details: %s',
                $this->filterValue['from'],
                $e->getMessage()
            ));
        }
    }

    public function getToAsCarbon(): Carbon
    {
        if (!isset($this->filterValue['to'])) {
            throw new InvalidArgumentException('Filter value for "to" must be set');
        }

        try {
            return Carbon::parse($this->filterValue['to']);
        } catch (InvalidFormatException $e) {
            throw new InvalidArgumentException(sprintf(
                'Invalid date format for "to": %s, Details: %s',
                $this->filterValue['to'],
                $e->getMessage()
            ));
        }
    }

    private function applySystemDatetimeFilter(
        ColumnFilter $column,
        QueryInterface $query,
        bool $roundToDay
    ): QueryInterface {

        if (!is_array($column->getFilterValue())) {
            throw new InvalidArgumentException('Filter value for this filter must be an array');
        }

        $this->setFilterValue($column->getFilterValue());

        $filterValue = $column->getFilterValue();

        if (isset($filterValue['from'], $filterValue['to'])) {
            $query->filterDatetime(
                $column->getKey(),
                $this->getFromAsCarbon(),
                $this->getToAsCarbon(),
                null,
                $roundToDay
            );

            return $query;
        }

        if (isset($filterValue['on'])) {
            $query->filterDatetime($column->getKey(), null, null, $this->getOnAsCarbon(), $roundToDay);
        }

        if (isset($filterValue['to'])) {
            $query->filterDatetime($column->getKey(), null, $this->getToAsCarbon(), null, $roundToDay);
        }

        if (isset($filterValue['from'])) {
            $query->filterDatetime($column->getKey(), $this->getFromAsCarbon(), null, null, $roundToDay);
        }

        return $query;
    }

    private function applyClassificationStoreDateFilter(
        ColumnFilter $column,
        DataObjectQueryInterface $query,
        KeyGroupRelation $key,
        GroupConfig $group,
        ClassificationStoreFilterValue $filterValue,
        bool $roundToDay
    ): QueryInterface {
        $this->setFilterValue($filterValue->getValue());

        if (isset($this->filterValue['from'], $this->filterValue['to'])) {

            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier(
                    $key->getName(),
                    $this->getFromAsCarbon(),
                    $this->getToAsCarbon(),
                    null,
                    $roundToDay
                ),
                $column->getLocale()
            );

            return $query;
        }

        if (isset($this->filterValue['on'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier(
                    $key->getName(),
                    null,
                    null,
                    $this->getOnAsCarbon(),
                    $roundToDay
                ),
                $column->getLocale()
            );
        }

        if (isset($this->filterValue['to'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier(
                    $key->getName(),
                    null,
                    $this->getToAsCarbon(),
                    null,
                    $roundToDay
                ),
                $column->getLocale()
            );
        }

        if (isset($this->filterValue['from'])) {
            $query->classificationStoreFilter(
                $column->getKeyWithOutLocale(),
                $group->getName(),
                $this->buildDateFilterModifier(
                    $key->getName(),
                    $this->getFromAsCarbon(),
                    null,
                    null,
                    $roundToDay
                ),
                $column->getLocale()
            );
            $query->filterDatetime($column->getKey(), $this->getFromAsCarbon());
        }

        return $query;
    }

    private function buildDateFilterModifier(
        string $field,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?Carbon $onDate = null,
        bool $roundToDay = true
    ): GDIDateFilter {
        return new GDIDateFilter($field, $startDate, $endDate, $onDate, $roundToDay);
    }
}
