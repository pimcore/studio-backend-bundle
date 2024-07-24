<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset;

use Pimcore\Bundle\StudioBackendBundle\Util\Traits\EnumToValueArrayTrait;

/**
 * @internal
 */
enum DownloadLimits: string
{
    use EnumToValueArrayTrait;

    case MAX_ZIP_FILE_SIZE = 'size_limit';
    case MAX_ZIP_FILE_AMOUNT = 'amount_limit';
}
