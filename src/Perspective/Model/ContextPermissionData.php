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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Model;

final readonly class ContextPermissionData
{
    public function __construct(
        private string $key,
        private string $group,
        private bool $defaultValue = true
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getDefaultValue(): bool
    {
        return $this->defaultValue;
    }
}
