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
interface PreviewConfigHydratorInterface
{
    /**
     * @param array<string, mixed> $rawEntry
     */
    public function hydratePreviewConfigEntry(array $rawEntry): PreviewConfigEntry;
}
