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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Util;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

enum Events: string
{
    use EnumToValueArrayTrait;

    case HANDLER_PROGRESS = 'handler-progress';
    case FINISHED_WITH_ERRORS = 'job-finished-with-errors';
    case FAILED = 'job-failed';
}
