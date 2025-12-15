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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Model;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ValidateElementTypeTrait;

final readonly class ContextPermissionData
{
    use ValidateElementTypeTrait;

    public function __construct(
        private string $key,
        private string $elementType,
        private bool $defaultValue = true
    ) {
        $this->validateStudioTypes($elementType);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getDefaultValue(): bool
    {
        return $this->defaultValue;
    }
}
