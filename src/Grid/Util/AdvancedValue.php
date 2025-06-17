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

final class AdvancedValue
{
    public function __construct(
        private string $type,
        private mixed $value
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
