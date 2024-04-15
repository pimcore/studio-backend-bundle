<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\Service\OpenApi;

use Codeception\Test\Unit;
use ErrorException;
use Pimcore\Bundle\StudioApiBundle\Service\OpenApiService;

final class OpenApiServiceTest extends Unit
{
    public function getConfigTest(): void
    {
        $openApiService = new OpenApiService([]);
        $config = $openApiService->getConfig();

        $this->assertSame('3.0.0', $config->openapi);
    }

    public function getConfigTestWithCustomPaths(): void
    {
        $openApiService = new OpenApiService([
            'src/Util/',
        ]);
        $config = $openApiService->getConfig();

        $this->assertSame('3.0.0', $config->openapi);
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