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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Util;

use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
final readonly class ColumnFieldDefinition
{
    public function __construct(
        private Data $fieldDefinition,
        private array $group,
        private bool $localizedField,
    ) {
    }

    public function getFieldDefinition(): Data
    {
        return $this->fieldDefinition;
    }

    public function getGroup(): array
    {
        return $this->group;
    }

    public function isLocalized(): bool
    {
        return $this->localizedField;
    }
}
