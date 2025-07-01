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

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class RedirectListEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.bundle_seo.redirect.list';

    public function __construct(
        private readonly Redirect $redirect
    ) {
        parent::__construct($redirect);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }
}
