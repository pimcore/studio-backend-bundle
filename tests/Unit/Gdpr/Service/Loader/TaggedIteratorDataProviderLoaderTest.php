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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Gdpr\Service\Loader;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\Loader\TaggedIteratorDataProviderLoader;

/**
 * @internal
 */
final class TaggedIteratorDataProviderLoaderTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetDataProvidersIsKeyedByProviderKey(): void
    {
        $dataObjects = $this->createProvider('data_objects');
        $assets = $this->createProvider('assets');

        $loader = new TaggedIteratorDataProviderLoader([$dataObjects, $assets]);

        $providers = $loader->getDataProviders();

        $this->assertSame(['data_objects', 'assets'], array_keys($providers));
        $this->assertSame($dataObjects, $providers['data_objects']);
        $this->assertSame($assets, $providers['assets']);
    }

    /**
     * @throws Exception
     */
    public function testResolveReturnsMatchingProvider(): void
    {
        $dataObjects = $this->createProvider('data_objects');
        $assets = $this->createProvider('assets');

        $loader = new TaggedIteratorDataProviderLoader([$dataObjects, $assets]);

        $this->assertSame($assets, $loader->resolve('assets'));
    }

    /**
     * @throws Exception
     */
    public function testResolveThrowsNotFoundExceptionForUnknownKey(): void
    {
        $loader = new TaggedIteratorDataProviderLoader([$this->createProvider('data_objects')]);

        $this->expectException(NotFoundException::class);
        $loader->resolve('unknown_provider');
    }

    /**
     * @throws Exception
     */
    private function createProvider(string $key): DataProviderInterface
    {
        return $this->makeEmpty(DataProviderInterface::class, [
            'getKey' => $key,
        ]);
    }
}
