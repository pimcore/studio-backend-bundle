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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Util;

/**
 * @internal
 */
enum ClassDefinitionType: string
{
    case FieldCollection = 'fieldcollection';
    case ClassDefinition = 'class';
    case CustomLayout = 'customlayout';
    case ObjectBrick = 'objectbrick';

    /**
     * Import order defines the sequence in which types must be processed
     * during bulk import (dependencies: class before customlayout, etc.).
     *
     * @return self[]
     */
    public static function importOrder(): array
    {
        return [
            self::FieldCollection,
            self::ClassDefinition,
            self::CustomLayout,
            self::ObjectBrick,
        ];
    }

    public function icon(): string
    {
        return match ($this) {
            self::FieldCollection => 'fieldcollection',
            self::ClassDefinition => 'class',
            self::CustomLayout => 'custom_views',
            self::ObjectBrick => 'objectbricks',
        };
    }
}
