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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter\PatchAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\AdapterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function in_array;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements AdapterLoaderInterface
{
    public const ADAPTER_TAG = 'pimcore.studio_backend.patch_adapter';

    public function __construct(
        #[AutowireIterator(self::ADAPTER_TAG)]
        private readonly iterable $taggedAdapter,
    ) {
    }

    /**
     * @return array<int, PatchAdapterInterface>
     */
    public function loadAdapters(string $elementType): array
    {
        return array_filter(
            [...$this->taggedAdapter],
            static function (PatchAdapterInterface $adapter) use ($elementType) {
                return in_array($elementType, $adapter->supportedElementTypes(), true);
            }
        );
    }
}
