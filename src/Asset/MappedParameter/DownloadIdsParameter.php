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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final readonly class DownloadIdsParameter
{
    /** @param array<int> $items */
    public function __construct(
        private array $items
    ) {
    }

    /** @return array<int, ElementDescriptor> */
    public function getItems(): array
    {
        return array_map(static fn (int $id) => new ElementDescriptor('asset', $id), $this->items);
    }
}
