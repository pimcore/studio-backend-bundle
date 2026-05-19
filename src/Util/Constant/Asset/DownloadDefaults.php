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

/**
 * @internal
 */
enum DownloadDefaults: string
{
    case DEFAULT_ZIP_FILENAME = 'assets.zip';
    case DEFAULT_FOLDER_NAME = 'assets';
    case ZIP_EXTENSION = '.zip';
}
