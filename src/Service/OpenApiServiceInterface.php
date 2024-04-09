<?php

namespace Pimcore\Bundle\StudioApiBundle\Service;

use OpenApi\Annotations\OpenApi;

interface OpenApiServiceInterface
{
    public function getConfig(): OpenApi;
}