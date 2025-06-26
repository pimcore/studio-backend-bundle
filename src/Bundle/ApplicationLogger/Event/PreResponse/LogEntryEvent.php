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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema\LogEntry;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class LogEntryEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.bundle_application_logger.list';

    public function __construct(
        private readonly LogEntry $logEntry
    ) {
        parent::__construct($logEntry);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getLogEntry(): LogEntry
    {
        return $this->logEntry;
    }
}
