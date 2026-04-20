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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 *
 * Implemented by data adapters that need to expose a dedicated value for the
 * detail page of a data object (as opposed to the generic normalized value
 * used e.g. for the grid). When implemented, the output takes precedence over
 * the standard {@see DataNormalizerInterface::normalize()} result within the
 * detail data flow only.
 */
interface DetailDataInterface
{
    public function getDetailData(
        Concrete $object,
        mixed $value,
        Data $fieldDefinition,
        ?FieldContextData $contextData = null,
    ): mixed;
}
