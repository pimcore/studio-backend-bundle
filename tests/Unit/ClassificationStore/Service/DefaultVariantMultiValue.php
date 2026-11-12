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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;

/**
 * A core `multiValue` definition of a core that declares the default variant setting.
 *
 * @internal
 */
final class DefaultVariantMultiValue extends MultiValue
{
    public function __construct(
        private readonly bool $allowed,
        private readonly ?string $label = null,
    ) {
    }

    public function isDefaultVariantAllowed(): bool
    {
        return $this->allowed;
    }

    public function getDefaultVariantLabel(): ?string
    {
        return $this->label;
    }
}
