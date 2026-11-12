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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\OwnershipProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\ProviderLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorProviderLoader implements ProviderLoaderInterface
{
    /**
     * @param iterable<OwnershipProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(self::OWNERSHIP_PROVIDER_TAG)]
        private iterable $providers,
    ) {
    }

    public function getProviders(): array
    {
        $providers = [];
        foreach ($this->providers as $provider) {
            $providers[$provider->getType()] = $provider;
        }

        return $providers;
    }

    public function resolve(string $type): OwnershipProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getType() === $type) {
                return $provider;
            }
        }

        throw new NotFoundException('Ownership configuration provider', $type, 'type');
    }
}
