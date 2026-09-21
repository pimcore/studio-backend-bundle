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

/**
 * Report configuration properties that travel between environments via export, import and clone.
 * Values are the PHP types (as returned by gettype()) a property may hold.
 *
 * @internal
 */
final class TransferableProperties
{
    public const array TYPES = [
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

    public const array STRING_LIST_PROPERTIES = ['sharedUserNames', 'sharedRoleNames'];

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
}
