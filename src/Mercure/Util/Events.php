<?php

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Util;

use Pimcore\Bundle\StudioBackendBundle\Util\Traits\EnumToValueArrayTrait;

enum Events: string
{
    use EnumToValueArrayTrait;
    case HANDLER_PROGRESS = 'handler-progress';
}