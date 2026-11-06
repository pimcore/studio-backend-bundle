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

/**
 * @internal
 *
 * @see AdvancedColumnSourceFieldContextInterface
 */
final class AdvancedColumnSourceFieldContext implements AdvancedColumnSourceFieldContextInterface
{
    private bool $resolvingSourceField = false;

    public function isResolvingSourceField(): bool
    {
        return $this->resolvingSourceField;
    }

    public function setResolvingSourceField(bool $resolvingSourceField): void
    {
        $this->resolvingSourceField = $resolvingSourceField;
    }
}
