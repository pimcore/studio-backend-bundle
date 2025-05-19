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
    case DELETION_FINISHED = 'deletion-finished';
    case ASSET_UPLOAD_FINISHED = 'asset-upload-finished';
}
