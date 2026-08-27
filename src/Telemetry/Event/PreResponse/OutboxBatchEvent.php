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
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxBatch;

final class OutboxBatchEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.telemetry.outbox_batch';

    public function __construct(
        private readonly OutboxBatch $outboxBatch
    ) {
        parent::__construct($this->outboxBatch);
    }

    public function getOutboxBatch(): OutboxBatch
    {
        return $this->outboxBatch;
    }
}
