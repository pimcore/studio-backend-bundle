<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants;

use Pimcore\Bundle\StudioBackendBundle\Util\Traits\EnumToValueArrayTrait;

enum Csv: string
{
    use EnumToValueArrayTrait;

    case JOB_STEP_CONFIG_SETTINGS = 'settings';
    case JOB_STEP_CONFIG_CONFIGURATION = 'configuration';
    case SETTINGS_DELIMITER = 'delimiter';
    case SETTINGS_HEADER = 'header';

    case SETTINGS_HEADER_NO_HEADER = 'no_header';
    case SETTINGS_HEADER_TITLE = 'title';
    case SETTINGS_HEADER_NAME = 'name';

    case NEW_LINE = "\r\n";
}