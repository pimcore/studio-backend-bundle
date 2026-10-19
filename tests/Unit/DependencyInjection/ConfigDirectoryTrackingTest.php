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
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\PimcoreStudioBackendExtension;
use ReflectionMethod;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;

/**
 * Configurations written at runtime with the symfony-config target (MCP servers,
 * perspectives, ...) are new YAML files in their write-target directory. The container
 * has to notice a new file there, or the configuration stays invisible until something
 * else rebuilds it: Pimcore's touch of config/services.yaml is skipped whenever PHP-FPM
 * cannot write that file. Core imports its own config directories the same way.
 *
 * @internal
 */
final class ConfigDirectoryTrackingTest extends Unit
{
    private string $directory;

    protected function _before(): void
    {
        $this->directory = sys_get_temp_dir() . '/' . uniqid('studio-config-', true);
    }

    protected function _after(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testANewFileInAnExistingDirectoryMakesTheContainerStale(): void
    {
        (new Filesystem())->mkdir($this->directory);
        $container = $this->prepend();
        $builtAt = time();

        touch($this->directory . '/test-server.yaml', $builtAt + 10);

        $this->assertTrue($this->isStale($container, $builtAt));
    }

    /**
     * The first configuration saved creates the directory itself.
     */
    public function testTheDirectoryAppearingMakesTheContainerStale(): void
    {
        $container = $this->prepend();
        $builtAt = time();

        (new Filesystem())->mkdir($this->directory);
        touch($this->directory, $builtAt + 10);

        $this->assertTrue($this->isStale($container, $builtAt));
    }

    public function testAnUnchangedDirectoryKeepsTheContainerFresh(): void
    {
        (new Filesystem())->mkdir($this->directory);
        touch($this->directory, time() - 10);
        $container = $this->prepend();

        $this->assertFalse($this->isStale($container, time()));
    }

    private function prepend(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $containerConfig = [
            'config_location' => [
                Configuration::MCP_SERVERS_NODE => [
                    'write_target' => ['options' => ['directory' => $this->directory]],
                ],
            ],
        ];

        (new ReflectionMethod(PimcoreStudioBackendExtension::class, 'prependCustomConfig'))
            ->invoke(new PimcoreStudioBackendExtension(), $container, $containerConfig, Configuration::MCP_SERVERS_NODE);

        return $container;
    }

    private function isStale(ContainerBuilder $container, int $builtAt): bool
    {
        foreach ($container->getResources() as $resource) {
            if ($resource instanceof SelfCheckingResourceInterface && !$resource->isFresh($builtAt)) {
                return true;
            }
        }

        return false;
    }
}
