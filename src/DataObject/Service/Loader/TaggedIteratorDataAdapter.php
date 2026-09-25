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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function get_class;
use function sprintf;

/**
 * @internal
 */
final readonly class TaggedIteratorDataAdapter implements DataAdapterLoaderInterface
{
    public function __construct(
        #[AutowireIterator(self::ADAPTER_TAG)]
        private iterable $taggedAdapter,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function loadAdapter(string $adapterClass): SetterDataInterface
    {
        $adapters = [...$this->taggedAdapter];
        /** @var SetterDataInterface $adapter */
        foreach ($adapters as $adapter) {
            if (get_class($adapter) === $adapterClass) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException(
            sprintf('No adapter found for the class "%s"', $adapterClass)
        );
    }
}
