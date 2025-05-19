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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntryParameter;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class EmailLogEntryParamsEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.email.log.detail.params';

    public function __construct(
        private readonly EmailLogEntryParameter $emailLogEntryParameter
    ) {
        parent::__construct($emailLogEntryParameter);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getEmailLogEntry(): EmailLogEntryParameter
    {
        return $this->emailLogEntryParameter;
    }
}
