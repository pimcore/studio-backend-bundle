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
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidPathException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiService;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;

final class OpenApiServiceTest extends Unit
{
    public function testExportConfigReplacesThePrefixAndSortsTagsDeterministically(): void
    {
        $spec = $this->createService()->exportConfig();

        $this->assertSame('3.1.0', $spec['openapi']);
        $this->assertSame(
            [
                '/studio-test/api/fixtures/{id}',
                '/studio-test/api/bundle/sample/config',
                '/studio-test/api/bundle/sample-other/config',
            ],
            array_keys($spec['paths'])
        );
        // core tags come first, "Bundle *" tags last — regardless of declaration order
        $this->assertSame(['Fixtures', 'Bundle Sample'], array_column($spec['tags'], 'name'));
    }

    public function testExportConfigLeavesSummariesRawWithoutALocaleAndTranslatesWithOne(): void
    {
        $service = $this->createService();

        $raw = $service->exportConfig();
        $translated = $service->exportConfig(locale: 'en');

        $this->assertSame(
            'fixture_get_by_id_summary',
            $raw['paths']['/studio-test/api/fixtures/{id}']['get']['summary']
        );
        $this->assertSame(
            'translated(fixture_get_by_id_summary)',
            $translated['paths']['/studio-test/api/fixtures/{id}']['get']['summary']
        );
    }

    public function testExportConfigRestrictsToScopesSegmentAwareWithComponentClosure(): void
    {
        $spec = $this->createService()->exportConfig(scopes: ['/studio-test/api/bundle/sample']);

        $this->assertSame(['/studio-test/api/bundle/sample/config'], array_keys($spec['paths']));
        // SampleConfig references Fixture, so both stay; tags shrink to the used one
        $this->assertSame(['Fixture', 'SampleConfig'], array_keys($spec['components']['schemas']));
        $this->assertSame(['Bundle Sample'], array_column($spec['tags'], 'name'));
    }

    public function testExportConfigRejectsAnInvalidScanPath(): void
    {
        $this->expectException(InvalidPathException::class);

        $this->createService()->exportConfig(scanPaths: [__DIR__ . '/does-not-exist']);
    }

    private function createService(): OpenApiService
    {
        $translator = $this->makeEmpty(TranslatorServiceInterface::class, [
            'translateApiDocs' => static fn (string $message, string $locale = 'en'): string
                => sprintf('translated(%s)', $message),
        ]);

        return new OpenApiService(
            $this->makeEmpty(PimcoreBundleManager::class),
            $translator,
            '/studio-test/api',
            [__DIR__ . '/Fixtures']
        );
    }
}
