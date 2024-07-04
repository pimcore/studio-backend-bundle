<?php

namespace Pimcore\Bundle\StudioBackendBundle\Util\Constants;

use Pimcore\Bundle\StudioBackendBundle\Util\Traits\EnumToValueArrayTrait;

enum DownloadFormats: string
{
    use EnumToValueArrayTrait;

    case CSV = 'csv';
    case ZIP = 'zip';
}
