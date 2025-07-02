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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectImportStats;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class RedirectImportStatsEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.bundle_seo.redirect.import_stats';

    public function __construct(
        private readonly RedirectImportStats $importStats
    ) {
        parent::__construct($importStats);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getRedirectImportStats(): RedirectImportStats
    {
        return $this->importStats;
    }
}
