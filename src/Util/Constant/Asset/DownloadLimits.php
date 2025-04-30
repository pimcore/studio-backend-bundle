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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum DownloadLimits: string
{
    use EnumToValueArrayTrait;

    case MAX_ZIP_FILE_SIZE = 'size_limit';
    case MAX_ZIP_FILE_AMOUNT = 'amount_limit';
}
