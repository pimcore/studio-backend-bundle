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
use function array_key_exists;

/**
 * Builds the real core `multiValue` definition from a `definition` blob, the way core hydrates it.
 *
 * Deliberately not a subclass: a stand-in that declared its own accessors let the resolver read a
 * method core does not have, and `method_exists` turned the mismatch into a silent "permitted".
 *
 * @internal
 */
final class DeclaredMultiValue
{
    public static function from(array $definition): MultiValue
    {
        $multiValue = new MultiValue();

        if (array_key_exists('unqualifiedValue', $definition)) {
            $multiValue->setUnqualifiedValue((bool) $definition['unqualifiedValue']);
        }

        if (array_key_exists('unqualifiedValueLabel', $definition)) {
            $multiValue->setUnqualifiedValueLabel($definition['unqualifiedValueLabel']);
        }

        return $multiValue;
    }
}
