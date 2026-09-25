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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxAckResult;

final class OutboxAckResultEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.telemetry.outbox_ack_result';

    public function __construct(
        private readonly OutboxAckResult $outboxAckResult
    ) {
        parent::__construct($this->outboxAckResult);
    }

    public function getOutboxAckResult(): OutboxAckResult
    {
        return $this->outboxAckResult;
    }
}
