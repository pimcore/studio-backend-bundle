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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Updater\Adapter\EditableUpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\EditableAdapterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use function in_array;

/**
 * @internal
 */
final class TaggedIteratorEditableAdapter implements EditableAdapterLoaderInterface
{
    public const ADAPTER_TAG = 'pimcore.studio_backend.editable.update_adapter';

    public function __construct(
        #[TaggedIterator(self::ADAPTER_TAG)]
        private readonly iterable $taggedAdapter,
    ) {
    }

    /**
     * @return array<int, EditableUpdateAdapterInterface>
     */
    public function loadAdapters(): array
    {
        return [...$this->taggedAdapter];
    }
}
