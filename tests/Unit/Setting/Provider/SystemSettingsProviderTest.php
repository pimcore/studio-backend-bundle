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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Setting\Provider;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolver;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\VersionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\SystemSettingsProvider;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\AdminLanguageServiceInterface;
use Pimcore\SystemSettingsConfig;
use ReflectionMethod;

/**
 * Regression tests for pimcore/platform-version#206: the Studio "Appearance & Branding"
 * settings could not be loaded (ErrorException: Undefined array key "error_pages") whenever
 * the `documents` system settings did not already contain a fully populated `error_pages`
 * structure, e.g. right after switching config_location to `settings-store`.
 *
 * @internal
 */
final class SystemSettingsProviderTest extends Unit
{
    public function testGetDocumentSettingsHandlesMissingErrorPagesKey(): void
    {
        $provider = $this->buildProvider([
            'documents' => [
                'versions' => ['days' => 10, 'steps' => 5],
            ],
        ]);

        $result = $this->invokeGetDocumentSettings($provider);

        $this->assertSame([], $result['error_pages']);
    }

    public function testGetDocumentSettingsHandlesMissingLocalizedErrorPagesKey(): void
    {
        $provider = $this->buildProvider([
            'documents' => [
                'versions' => ['days' => 10, 'steps' => 5],
                'error_pages' => ['default' => null],
            ],
        ]);

        $result = $this->invokeGetDocumentSettings($provider);

        $this->assertSame(['default' => null], $result['error_pages']);
    }

    private function buildProvider(array $systemSettings): SystemSettingsProvider
    {
        return new SystemSettingsProvider(
            $this->makeEmpty(SystemSettingsConfig::class, [
                'getSystemSettingsConfig' => $systemSettings,
            ]),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(VersionResolverInterface::class),
            new ConfigResolver(),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementDataServiceInterface::class),
            $this->makeEmpty(AdminLanguageServiceInterface::class),
        );
    }

    private function invokeGetDocumentSettings(SystemSettingsProvider $provider): array
    {
        $method = new ReflectionMethod($provider, 'getDocumentSettings');

        return $method->invoke($provider);
    }
}
