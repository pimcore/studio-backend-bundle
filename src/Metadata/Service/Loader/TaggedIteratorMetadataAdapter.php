<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function get_class;
use function sprintf;

/**
 * @internal
 */
final readonly class TaggedIteratorMetadataAdapter implements DataAdapterLoaderInterface
{
    public function __construct(
        #[TaggedIterator(AdapterLoader::METADATA_ADAPTER_TAG->value)]
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
