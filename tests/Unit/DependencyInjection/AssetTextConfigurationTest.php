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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * The `asset_text_settings.max_file_size` node sets the size above which Studio refuses to load a text
 * asset. It defaults to the former hard-coded 2 MB.
 *
 * @internal
 */
final class AssetTextConfigurationTest extends Unit
{
    public function testDefaultIsTwoMegabytes(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[]]);

        $this->assertSame(2000000, $processed['asset_text_settings']['max_file_size']);
    }

    public function testAcceptsCustomLimit(): void
    {
        $this->assertSame(5000000, $this->process(5000000));
    }

    public function testRejectsZero(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(0);
    }

    public function testRejectsNegativeLimit(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(-1);
    }

    private function process(int $maxFileSize): int
    {
        $config = [['asset_text_settings' => ['max_file_size' => $maxFileSize]]];

        return (new Processor())->processConfiguration(new Configuration(), $config)['asset_text_settings']['max_file_size'];
    }
}
