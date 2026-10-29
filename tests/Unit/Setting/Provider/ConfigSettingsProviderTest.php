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
use Pimcore\Bundle\StudioBackendBundle\Setting\Provider\ConfigSettingsProvider;
use Pimcore\Config;

final class ConfigSettingsProviderTest extends Unit
{
    protected function _after(): void
    {
        Config::setSystemConfiguration(null);
    }

    public function testExposesObjectAutoSaveInterval(): void
    {
        Config::setSystemConfiguration($this->buildConfiguration(objectAutoSaveInterval: 60));

        $settings = (new ConfigSettingsProvider(new Config()))->getSettings();

        $this->assertSame(60, $settings['object_auto_save_interval']);
    }

    public function testExposesDisabledObjectAutoSave(): void
    {
        Config::setSystemConfiguration($this->buildConfiguration(objectAutoSaveInterval: 0));

        $settings = (new ConfigSettingsProvider(new Config()))->getSettings();

        $this->assertSame(0, $settings['object_auto_save_interval']);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildConfiguration(int $objectAutoSaveInterval): array
    {
        return [
            'assets' => [
                'tree_paging_limit' => 100,
                'frontend_prefixes' => ['source' => ''],
            ],
            'documents' => ['tree_paging_limit' => 50],
            'objects' => [
                'tree_paging_limit' => 30,
                'auto_save_interval' => $objectAutoSaveInterval,
            ],
            'general' => ['timezone' => 'UTC'],
            'maps' => [],
        ];
    }
}
