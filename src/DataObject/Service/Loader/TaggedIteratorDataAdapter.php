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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Loader;

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorDataAdapter implements DataAdapterLoaderInterface
{
    public function __construct(
        #[TaggedIterator(self::ADAPTER_TAG)]
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
