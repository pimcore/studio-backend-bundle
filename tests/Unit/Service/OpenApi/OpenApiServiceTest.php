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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\OpenApi;

use Codeception\Test\Unit;
use ErrorException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService;

final class OpenApiServiceTest extends Unit
{
    public function getConfigTest(): void
    {
        $openApiService = new OpenApiService([]);
        $config = $openApiService->getConfig();

        $this->assertSame('3.1.0', $config->openapi);
    }

    public function getConfigTestWithCustomPaths(): void
    {
        $openApiService = new OpenApiService([
            'src/Util/',
        ]);
        $config = $openApiService->getConfig();

        $this->assertSame('3.1.0', $config->openapi);
    }

    public function getConfigTestWithCustomPathsException(): void
    {
        $openApiService = new OpenApiService([
            'testPath',
        ]);

        $this->expectException(ErrorException::class);
        $openApiService->getConfig();
    }
}
