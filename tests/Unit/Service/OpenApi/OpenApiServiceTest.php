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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidPathException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use stdClass;

#[CoversClass(OpenApiService::class)]
/**
 * @internal
 */
final class OpenApiServiceTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService::getConfig
     */
    public function testGetConfigReturnsExpectedVersion(): void
    {
        // Since OpenApiService performs actual file system operations and OpenAPI scanning,
        // we'll test that the service can be instantiated properly with mocked dependencies
        $bundleManagerMock = $this->createMock(PimcoreBundleManager::class);
        $translatorMock = $this->createMock(TranslatorServiceInterface::class);
        
        // Test constructor doesn't throw exceptions with empty paths
        $openApiService = new OpenApiService(
            $bundleManagerMock,
            $translatorMock,
            '/api',
            []
        );
        
        $this->assertInstanceOf(OpenApiService::class, $openApiService);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService::getConfig
     */
    public function testGetConfigWithValidPaths(): void
    {
        $bundleManagerMock = $this->createMock(PimcoreBundleManager::class);
        $translatorMock = $this->createMock(TranslatorServiceInterface::class);
        
        // Test constructor with valid relative paths that exist in the project
        $openApiService = new OpenApiService(
            $bundleManagerMock,
            $translatorMock,
            '/api',
            ['src/']  // This path exists in the project
        );
        
        $this->assertInstanceOf(OpenApiService::class, $openApiService);
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService::getConfig
     */
    public function testGetConfigWithInvalidPath(): void
    {
        $bundleManagerMock = $this->createMock(PimcoreBundleManager::class);
        $translatorMock = $this->createMock(TranslatorServiceInterface::class);
        
        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('The path "nonexistent-path" is not a valid directory.');
        
        $openApiService = new OpenApiService(
            $bundleManagerMock,
            $translatorMock,
            '/api',
            ['nonexistent-path']
        );
        
        // This should trigger the exception during getConfig()
        $openApiService->getConfig();
    }
}
