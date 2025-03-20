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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject;


/**
 * @internal
 */
final readonly class TableDefinition extends AbstractDefinition
{
    public function getType(): string
    {
        return 'data-object.table';
    }

    public function getFrontendType(): string
    {
        return 'table';
    }

    #[\Override]
    public function isSortable(): bool
    {
        return false;
    }

    #[\Override]
    public function isFilterable(): bool
    {
        return false;
    }

    #[\Override]
    public function isExportable(): bool
    {
        return false;
    }
}
