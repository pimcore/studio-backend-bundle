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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant;

/**
 * Stands for a property the request payload did not carry, which a partial write has to tell apart
 * from one carrying an explicit null.
 *
 * @internal
 */
enum AbsentValue: string
{
    case ABSENT = 'absent';
}
