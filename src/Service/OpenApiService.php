<?php

namespace Pimcore\Bundle\StudioApiBundle\Service;

use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;

final class OpenApiService implements OpenApiServiceInterface
{
    private const PATH_PREFIX = __DIR__ . '/../../';
    private const PATHS = [
        self::PATH_PREFIX . 'src/Controller/Api',
        self::PATH_PREFIX . 'src/Dto/',
        self::PATH_PREFIX . 'src/Config/',
    ];

    public function getConfig(): OpenApi
    {
        return Generator::scan(self::PATHS);
    }
}