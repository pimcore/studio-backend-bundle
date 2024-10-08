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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
final readonly class ColumnFieldDefinition
{
    public function __construct(
        private Data $fieldDefinition,
        private string $group,
        private bool $localizedField,
    )
    {
    }

    public function getFieldDefinition(): Data
    {
        return $this->fieldDefinition;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function isLocalized(): bool
    {
        return $this->localizedField;
    }
}