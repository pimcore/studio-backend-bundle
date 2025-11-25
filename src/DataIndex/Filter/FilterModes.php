<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum FilterModes: string
{
    use EnumToValueArrayTrait;

    case IS = 'is';
    case LESS = 'less';
    CASE MORE = 'more';
    CASE BETWEEN = 'between';
}
