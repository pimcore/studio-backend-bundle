<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util;

enum JobSteps: string
{
    case ZIP_COLLECTION = 'Zip Collection';
    case ZIP_CREATION = 'Zip Creation';
}
