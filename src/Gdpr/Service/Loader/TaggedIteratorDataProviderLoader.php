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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\DataProviderLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorDataProviderLoader implements DataProviderLoaderInterface
{
    public function __construct(
        #[AutowireIterator(self::DATA_PROVIDER_TAG)]
        private iterable $providers,
    ) {
    }

    public function getDataProviders(): array
    {
        $providers = [];
        foreach ($this->providers as $provider) {
            $providers[$provider->getKey()] = $provider;
        }

        return $providers;
    }

    public function resolve(string $key): DataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getKey() === $key) {
                return $provider;
            }
        }

        throw new NotFoundException('GDPR data provider', $key, 'key');
    }
}
