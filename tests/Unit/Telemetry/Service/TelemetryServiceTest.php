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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Telemetry\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Event\PreResponse\OutboxAckResultEvent;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Event\PreResponse\OutboxBatchEvent;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Hydrator\TelemetryHydrator;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Service\TelemetryService;
use Pimcore\Telemetry\Spool\EncryptedBatch;
use Pimcore\Telemetry\Spool\TelemetryOutboxInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class TelemetryServiceTest extends Unit
{
    private const RELAY = 'https://license.pimcore.com/telemetry/v1/ingest';

    /**
     * An instance without an identity or product key cannot produce a decryptable batch, so the
     * outbox must not even be claimed - claiming would lease rows that can never be delivered.
     */
    public function testAnOutboxThatIsNotReadyIsNeverClaimed(): void
    {
        $outbox = $this->createMock(TelemetryOutboxInterface::class);
        $outbox->method('isReady')->willReturn(false);
        $outbox->expects($this->never())->method('nextBatch');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $this->assertNull($this->service($outbox, $dispatcher)->getNextBatch());
    }

    public function testAnEmptyOutboxYieldsNoBatch(): void
    {
        $outbox = $this->createMock(TelemetryOutboxInterface::class);
        $outbox->method('isReady')->willReturn(true);
        $outbox->method('nextBatch')->willReturn(null);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $this->assertNull($this->service($outbox, $dispatcher)->getNextBatch());
    }

    /**
     * The browser is only a courier: it receives the opaque ciphertext plus the relay address to
     * post it to. The relay endpoint is server-side configuration and must be hydrated in here.
     */
    public function testAClaimedBatchIsHydratedWithTheRelayEndpointAndAnnounced(): void
    {
        $outbox = $this->createMock(TelemetryOutboxInterface::class);
        $outbox->method('isReady')->willReturn(true);
        $outbox->method('nextBatch')->willReturn(new EncryptedBatch('nonce-1', 'inst-1', 'opaque', 3));

        $dispatched = null;
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(OutboxBatchEvent::class),
                OutboxBatchEvent::EVENT_NAME
            )
            ->willReturnCallback(static function (object $event) use (&$dispatched): object {
                $dispatched = $event;

                return $event;
            });

        $batch = $this->service($outbox, $dispatcher)->getNextBatch();

        $this->assertNotNull($batch);
        $this->assertSame('nonce-1', $batch->getNonce());
        $this->assertSame('inst-1', $batch->getInstanceIdentifier());
        $this->assertSame('opaque', $batch->getCiphertext());
        $this->assertSame(self::RELAY, $batch->getRelayEndpoint());
        $this->assertSame($batch, $dispatched?->getOutboxBatch());
    }

    public function testAckDelegatesToTheOutboxAndReportsHowManyEventsLeft(): void
    {
        $outbox = $this->createMock(TelemetryOutboxInterface::class);
        $outbox->expects($this->once())->method('ack')->with('nonce-1')->willReturn(7);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OutboxAckResultEvent::class), OutboxAckResultEvent::EVENT_NAME)
            ->willReturnArgument(0);

        $result = $this->service($outbox, $dispatcher)->ackBatch('nonce-1');

        $this->assertSame(7, $result->getAcked());
    }

    /**
     * 0 is the row count of a DELETE that matched nothing - an unknown nonce, or one whose lease
     * expired and was released back to pending - not an error. It is reported as-is so the caller
     * can tell "removed" from "already gone": a client that treats 0 as success would go on
     * draining against a lease it no longer holds. A genuine storage failure throws instead.
     */
    public function testAnAckThatRemovedNothingIsReportedAsZero(): void
    {
        $outbox = $this->createMock(TelemetryOutboxInterface::class);
        $outbox->method('ack')->willReturn(0);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $this->assertSame(0, $this->service($outbox, $dispatcher)->ackBatch('unknown')->getAcked());
    }

    private function service(
        TelemetryOutboxInterface $outbox,
        EventDispatcherInterface $dispatcher,
    ): TelemetryService {
        return new TelemetryService($outbox, new TelemetryHydrator(), $dispatcher, self::RELAY);
    }
}
