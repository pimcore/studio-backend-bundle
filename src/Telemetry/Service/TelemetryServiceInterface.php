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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry\Service;

use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxAckResult;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxBatch;

/**
 * @internal
 */
interface TelemetryServiceInterface
{
    /**
     * Returns the next encrypted outbox batch ready to be forwarded to the relay,
     * or null when the outbox is not ready or the pool is empty.
     */
    public function getNextBatch(): ?OutboxBatch;

    public function ackBatch(string $nonce): OutboxAckResult;
}
