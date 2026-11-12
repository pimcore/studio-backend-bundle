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

namespace Pimcore\Bundle\StudioBackendBundle\Filter;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * Operators that replace the value of a column filter, they work for every column type.
 *
 * @internal
 */
enum FilterOperator: string
{
    use EnumToValueArrayTrait;

    case EMPTY = 'empty';
    case NOT_EMPTY = 'notEmpty';
}
