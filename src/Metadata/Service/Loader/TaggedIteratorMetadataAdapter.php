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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function get_class;
use function sprintf;

/**
 * @internal
 */
final readonly class TaggedIteratorMetadataAdapter implements DataAdapterLoaderInterface
{
    public function __construct(
        #[AutowireIterator(AdapterLoader::METADATA_ADAPTER_TAG->value)]
        private iterable $taggedAdapter,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function loadAdapter(string $adapterClass): MetaDataAdapterInterface
    {
        $adapters = [...$this->taggedAdapter];
        /** @var MetaDataAdapterInterface $adapter */
        foreach ($adapters as $adapter) {
            if (get_class($adapter) === $adapterClass) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException(
            sprintf('No adapter found for the metadata adapter class "%s"', $adapterClass)
        );
    }
}
