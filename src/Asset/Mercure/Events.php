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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Mercure;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\EnumToValueArrayTrait;

/**
 * @internal
 */
enum Events: string
{
    use EnumToValueArrayTrait;

    case ZIP_DOWNLOAD_READY = 'zip-download-ready';
    case ZIP_UPLOAD_FINISHED = 'zip-upload-finished';
    case CSV_DOWNLOAD_READY = 'csv-download-ready';
    case DELETION_FINISHED = 'deletion-finished';
    case ASSET_UPLOAD_FINISHED = 'asset-upload-finished';
}
