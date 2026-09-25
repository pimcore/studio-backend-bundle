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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxAckResult;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxBatch;
use Pimcore\Telemetry\Spool\EncryptedBatch;

/**
 * @internal
 */
final readonly class TelemetryHydrator implements TelemetryHydratorInterface
{
    private const int PROTOCOL_VERSION = 1;

    public function hydrateOutboxBatch(EncryptedBatch $batch, string $relayEndpoint): OutboxBatch
    {
        return new OutboxBatch(
            $batch->nonce,
            $batch->instanceIdentifier,
            self::PROTOCOL_VERSION,
            $batch->ciphertext,
            $relayEndpoint,
        );
    }

    public function hydrateOutboxAckResult(int $acked): OutboxAckResult
    {
        return new OutboxAckResult($acked);
    }
}
