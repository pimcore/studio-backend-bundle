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

use Pimcore\Bundle\StudioBackendBundle\Telemetry\Event\PreResponse\OutboxAckResultEvent;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Event\PreResponse\OutboxBatchEvent;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Hydrator\TelemetryHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxAckResult;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxBatch;
use Pimcore\Telemetry\Spool\EncryptedBatch;
use Pimcore\Telemetry\Spool\TelemetryOutboxInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class TelemetryService implements TelemetryServiceInterface
{
    public function __construct(
        private TelemetryOutboxInterface $telemetryOutbox,
        private TelemetryHydratorInterface $telemetryHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private string $relayEndpoint,
    ) {
    }

    public function getNextBatch(): ?OutboxBatch
    {
        // Without a relay endpoint the browser would have nowhere to forward to, so do not even
        // claim/lease a batch (mirrors the maintenance RelayClient::isConfigured() guard). This
        // keeps the default config (empty relay_endpoint) from churning leases with no delivery.
        if ($this->relayEndpoint === '' || !$this->telemetryOutbox->isReady()) {
            return null;
        }

        $batch = $this->telemetryOutbox->nextBatch();

        if (!$batch instanceof EncryptedBatch) {
            return null;
        }

        $outboxBatch = $this->telemetryHydrator->hydrateOutboxBatch($batch, $this->relayEndpoint);

        $this->eventDispatcher->dispatch(
            new OutboxBatchEvent($outboxBatch),
            OutboxBatchEvent::EVENT_NAME
        );

        return $outboxBatch;
    }

    public function ackBatch(string $nonce): OutboxAckResult
    {
        $outboxAckResult = $this->telemetryHydrator->hydrateOutboxAckResult(
            $this->telemetryOutbox->ack($nonce)
        );

        $this->eventDispatcher->dispatch(
            new OutboxAckResultEvent($outboxAckResult),
            OutboxAckResultEvent::EVENT_NAME
        );

        return $outboxAckResult;
    }
}
