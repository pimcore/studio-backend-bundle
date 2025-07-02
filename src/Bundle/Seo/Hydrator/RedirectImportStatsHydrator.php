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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectImportStats;

/**
 * @internal
 */
final readonly class RedirectImportStatsHydrator implements RedirectImportStatsHydratorInterface
{
    public function hydrate(array $statistics): RedirectImportStats
    {
        return new RedirectImportStats(
            total: $statistics['total'],
            imported: $statistics['imported'],
            created: $statistics['created'],
            updated: $statistics['updated'],
            errored: $statistics['errored'],
            errors: $statistics['errors'] ?? [],
        );
    }
}
