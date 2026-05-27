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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\PreviewConfigEntry;

/**
 * @internal
 */
final readonly class PreviewConfigHydrator implements PreviewConfigHydratorInterface
{
    public function hydratePreviewConfigEntry(array $rawEntry): PreviewConfigEntry
    {
        $values = [];
        $entryValues = $rawEntry['values'] ?? [];
        foreach ($entryValues as $label => $value) {
            $values[] = ['key' => $label, 'value' => $value];
        }

        return new PreviewConfigEntry(
            $rawEntry['name'],
            $rawEntry['label'],
            $values,
            (string) $rawEntry['defaultValue'],
        );
    }
}
