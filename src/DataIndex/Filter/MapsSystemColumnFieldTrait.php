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

/**
 * Reconciles grid system column keys with their search index field name where the two differ.
 *
 * The data object grid exposes the class name column under the core key "classname"
 * (from Concrete::SYSTEM_COLUMN_NAMES), but the generic data index stores it as "className"
 * (SystemField::CLASS_NAME). Filtering or sorting therefore has to translate the key before
 * it is handed to the index query. Unmapped keys pass through unchanged.
 *
 * @internal
 */
trait MapsSystemColumnFieldTrait
{
    private function mapColumnKeyToIndexField(string $columnKey): string
    {
        $columnKeyToIndexField = [
            'classname' => 'className',
        ];

        return $columnKeyToIndexField[$columnKey] ?? $columnKey;
    }
}
