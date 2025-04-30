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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;

final class DocumentVersionEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.document_version';

    public function __construct(
        private readonly DocumentVersion $version
    ) {
        parent::__construct($version);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getVersion(): DocumentVersion
    {
        return $this->version;
    }
}
