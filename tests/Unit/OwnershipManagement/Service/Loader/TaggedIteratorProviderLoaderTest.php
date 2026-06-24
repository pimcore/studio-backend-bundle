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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OwnershipManagement\Service\Loader;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\OwnershipProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Loader\TaggedIteratorProviderLoader;

/**
 * @internal
 */
final class TaggedIteratorProviderLoaderTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetProvidersIsKeyedByType(): void
    {
        $grid = $this->createProvider('grid_configuration');
        $dashboard = $this->createProvider('dashboard');

        $loader = new TaggedIteratorProviderLoader([$grid, $dashboard]);

        $providers = $loader->getProviders();

        $this->assertSame(['grid_configuration', 'dashboard'], array_keys($providers));
        $this->assertSame($grid, $providers['grid_configuration']);
        $this->assertSame($dashboard, $providers['dashboard']);
    }

    /**
     * @throws Exception
     */
    public function testResolveReturnsMatchingProvider(): void
    {
        $grid = $this->createProvider('grid_configuration');
        $dashboard = $this->createProvider('dashboard');

        $loader = new TaggedIteratorProviderLoader([$grid, $dashboard]);

        $this->assertSame($dashboard, $loader->resolve('dashboard'));
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsNotFoundExceptionForUnknownType(): void
    {
        $loader = new TaggedIteratorProviderLoader([$this->createProvider('grid_configuration')]);

        $this->expectException(NotFoundException::class);
        $loader->resolve('unknown_type');
    }

    /**
     * @throws Exception
     */
    private function createProvider(string $type): OwnershipProviderInterface
    {
        return $this->makeEmpty(OwnershipProviderInterface::class, [
            'getType' => $type,
        ]);
    }
}
