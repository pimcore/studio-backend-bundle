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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\AdapterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements AdapterLoaderInterface
{
    public const ADAPTER_TAG = 'pimcore.studio_backend.update_adapter';

    public function __construct(
        #[TaggedIterator(self::ADAPTER_TAG)]
        private readonly iterable $taggedAdapter,
    ) {
    }

    /**
     * @return array<int, UpdateAdapterInterface>
     */
    public function loadAdapters(string $elementType): array
    {
        return array_filter(
            [...$this->taggedAdapter],
            static function (UpdateAdapterInterface $adapter) use ($elementType) {
                return in_array($elementType, $adapter->supportedElementTypes(), true);
            }
        );
    }
}
