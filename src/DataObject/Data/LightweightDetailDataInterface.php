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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

/**
 * Implemented by adapters that can build the detail data of a field without reading the field value from the
 * object, e.g. because reading it would load every related element.
 */
interface LightweightDetailDataInterface
{
    /**
     * Returns null when the regular way of reading the field value has to be used.
     */
    public function getLightweightDetailData(Concrete $object, Data $fieldDefinition, string $key): mixed;
}
