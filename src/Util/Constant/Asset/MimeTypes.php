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
enum MimeTypes: string
{
    use EnumToValueArrayTrait;

    case CSV = 'text/csv';
    case JPEG = 'JPEG';
    case ORIGINAL = 'original';
    case PRINT = 'print';
    case PJPEG = 'PJPEG';
    case PNG = 'PNG';
    case PDF = 'application/pdf';
    case SOURCE = 'source';
    case ZIP = 'application/zip';
    case JSON = 'application/json';
    case XLSX = 'application/xlsx';
    case GENERIC = '*/*';
}
