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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Site;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class SiteEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.document.sites_list_available';

    public function __construct(
        private readonly Site $site
    ) {
        parent::__construct($site);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getSite(): Site
    {
        return $this->site;
    }
}
