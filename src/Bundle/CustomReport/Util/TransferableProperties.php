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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Util;

use function array_intersect_key;
use function array_keys;
use function in_array;
use function is_array;

/**
 * Report configuration properties that travel between environments via export, import and clone,
 * together with the PHP types (as returned by gettype()) each property may hold.
 *
 * @internal
 */
final class TransferableProperties
{
    private const array TYPES = [
        'name' => ['string'],
        'sql' => ['string'],
        'dataSourceConfig' => ['array'],
        'columnConfiguration' => ['array'],
        'niceName' => ['string'],
        'group' => ['string'],
        'groupIconClass' => ['string'],
        'iconClass' => ['string'],
        'menuShortcut' => ['boolean'],
        'reportClass' => ['string'],
        'chartType' => ['string'],
        'pieColumn' => ['string', 'NULL'],
        'pieLabelColumn' => ['string', 'NULL'],
        'xAxis' => ['string', 'NULL'],
        'yAxis' => ['string', 'array', 'NULL'],
        'shareGlobally' => ['boolean'],
        'pagination' => ['boolean'],
        'sharedUserNames' => ['array'],
        'sharedRoleNames' => ['array'],
    ];

    private const array STRING_LIST_PROPERTIES = ['sharedUserNames', 'sharedRoleNames'];

    private const string COLUMN_CONFIGURATION = 'columnConfiguration';

    private const string DATA_SOURCE_CONFIG = 'dataSourceConfig';

    private const string DATA_SOURCE_TYPE_FIELD = 'type';

    private const string DEFAULT_DATA_SOURCE_TYPE = 'sql';

    private const array REQUIRED_COLUMN_FIELDS = ['name', 'display', 'export', 'order'];

    private const array COLUMN_FIELD_DEFAULTS = ['display' => false, 'export' => false, 'order' => false];

    private const array COLUMN_FIELD_TYPES = [
        'name' => ['string'],
        'display' => ['boolean'],
        'export' => ['boolean'],
        'order' => ['boolean'],
        'label' => ['string'],
        'action' => ['string'],
        'id' => ['string'],
        'width' => ['integer', 'string', 'NULL'],
        'displayType' => ['string', 'NULL'],
        'filter' => ['string', 'NULL'],
        'filter_drilldown' => ['string', 'NULL'],
    ];

    /**
     * @return string[]
     */
    public static function names(): array
    {
        return array_keys(self::TYPES);
    }

    public static function filter(array $data): array
    {
        return array_intersect_key($data, self::TYPES);
    }

    /**
     * Fills the boolean column flags legacy report definitions may omit, so exported files
     * and imported files carry the complete shape the column DTO requires. Structural
     * problems are left untouched for the validator to report.
     */
    public static function normalize(array $data): array
    {
        $columns = $data[self::COLUMN_CONFIGURATION] ?? null;
        if (!is_array($columns)) {
            return $data;
        }

        foreach ($columns as $index => $column) {
            if (is_array($column)) {
                $columns[$index] = $column + self::COLUMN_FIELD_DEFAULTS;
            }
        }

        $data[self::COLUMN_CONFIGURATION] = $columns;

        return $data;
    }

    /**
     * @return string[]
     */
    public static function allowedTypes(string $property): array
    {
        return self::TYPES[$property] ?? [];
    }

    public static function isStringList(string $property): bool
    {
        return in_array($property, self::STRING_LIST_PROPERTIES, true);
    }

    public static function isColumnConfiguration(string $property): bool
    {
        return $property === self::COLUMN_CONFIGURATION;
    }

    public static function isDataSourceConfig(string $property): bool
    {
        return $property === self::DATA_SOURCE_CONFIG;
    }

    /**
     * @return string[]
     */
    public static function requiredColumnFields(): array
    {
        return self::REQUIRED_COLUMN_FIELDS;
    }

    public static function dataSourceTypeField(): string
    {
        return self::DATA_SOURCE_TYPE_FIELD;
    }

    public static function defaultDataSourceType(): string
    {
        return self::DEFAULT_DATA_SOURCE_TYPE;
    }

    public static function columnFields(array $column): array
    {
        return array_intersect_key($column, self::COLUMN_FIELD_TYPES);
    }

    /**
     * @return string[]
     */
    public static function allowedColumnFieldTypes(string $field): array
    {
        return self::COLUMN_FIELD_TYPES[$field] ?? [];
    }
}
