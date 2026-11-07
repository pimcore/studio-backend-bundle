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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;
use function array_keys;
use function sprintf;

/**
 * Collects the key saves of a unit of work whose resolved search index flag changed, and hands one
 * remap per affected index over when that unit of work ends. A store is noted once however many of
 * its keys changed, and two stores reaching the same class remap that class once.
 *
 * The notes are dropped by {@see flush()}, which the key write surfaces call once per request or
 * per apply, and again at the end of the request when their own call could not hand anything over
 * yet; by {@see discard()}, which is what a rolled back apply leaves behind instead; and by
 * {@see reset()}: implementing ResetInterface makes autoconfiguration tag the service
 * `kernel.reset`, so a long-running worker starts each message with no note of the one before it.
 *
 * ## Why the hand-over waits for the transaction and not for the caller
 *
 * "Once the writes are committed" cannot be left to where the call sits. Both write surfaces join a
 * transaction they did not open when there is one - `KeyRepository::writeHoldingTheRow()` and
 * `Reconciler::apply()` each own theirs only when `isTransactionActive()` said no at the start -
 * and a flush that runs inside one still reads rows nobody else can see yet. The damage is not the
 * hand-over, which converges, but the coalescing: a class that already has a remap pending is taken
 * for covered, the note is dropped, and that remap's handler then rebuilds the mapping from the old
 * committed row and releases the marker - all before the caller commits. The change is left
 * committed with nothing pending and nothing noted anywhere, which is the stale mapping this whole
 * mechanism exists to prevent.
 *
 * So {@see flush()} hands nothing over while a transaction is open: it keeps the notes and returns,
 * and `SearchIndexRemapFlushSubscriber` flushes again on the request's or the command's terminate,
 * by which time no transaction of the unit of work can still be open. For every write surface this
 * bundle has, the flush they make themselves is already after the commit - each of them owns its
 * transaction and has committed it by then - so the deferral is what holds the contract for a
 * caller that owns the transaction instead.
 *
 * The one thing the deferral cannot see is whether that outermost transaction committed or rolled
 * back. It hands over either way, and deliberately: a remap of rows that were rolled back rebuilds
 * the mapping from the committed state and costs one superfluous re-index, where skipping one that
 * was needed costs the stale mapping. The rollbacks this bundle performs itself are still reported
 * exactly, by {@see discard()}.
 *
 * A store is skipped on its own marker only when that marker stands for complete coverage, which is
 * why {@see flush()} decides every class before it claims anything: a store may be marked as queued
 * only when every class it reaches is one this flush is handing over itself
 * ({@see SearchIndexRemapQueueInterface::claimStoreCoverage()}). A class another flush queued is
 * covered by a message that does not carry this store, so claiming on it would suppress this
 * store's later flag changes for that class after the message had already been handled.
 *
 * Nothing {@see flush()} does can fail the request it was called from: a store that cannot be
 * resolved to its indices and a remap that cannot be handed over are both logged and nothing more.
 * The key row is committed by then, and failing the request afterwards would cost the operator the
 * edit rather than the convergence - the remap is deferred, so the one thing it may not do is turn
 * the save it was triggered by into a 500.
 *
 * @internal
 */
final class SearchIndexRemapTrigger implements SearchIndexRemapTriggerInterface, ResetInterface
{
    /**
     * @var array<int, true> keyed by storeId
     */
    private array $noted = [];

    public function __construct(
        private readonly SearchIndexFlagResolverInterface $searchIndexFlagResolver,
        private readonly SearchIndexRemapTargetResolverInterface $remapTargetResolver,
        private readonly SearchIndexRemapQueueInterface $remapQueue,
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function noteFlagChange(int $storeId, ?string $definitionBefore, ?string $definitionAfter): void
    {
        $indexedBefore = $this->searchIndexFlagResolver->isIndexed($definitionBefore);
        $indexedAfter = $this->searchIndexFlagResolver->isIndexed($definitionAfter);

        if ($indexedBefore === $indexedAfter) {
            return;
        }

        $this->noted[$storeId] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        if ($this->noted !== [] && $this->isWriteUncommitted()) {
            // the notes stay: nothing may be coalesced away against rows the rest of the database
            // cannot see yet. The terminate subscriber flushes again once the transaction is gone.
            return;
        }

        $stores = array_keys($this->noted);
        // forgotten before the hand-over, so a failing one is not retried by the next flush
        $this->noted = [];
        $targets = [];
        $coverage = [];

        foreach ($stores as $storeId) {
            $this->collectTargets($storeId, $targets, $coverage);
        }

        // decided for every collected class before anything is claimed or dispatched, so that the
        // claim on a store and the hand-overs it stands for are the same answer
        $handOver = [];

        foreach ($targets as $key => $target) {
            $handOver[$key] = $this->isHandOverNeeded($target['classDefinitionId']);
        }

        // claimed before the first hand-over is dispatched, the way the class marker is written
        // before its own: a hand-over that then fails takes the claim back with it, and so does a
        // synchronous transport that handles its message inside `dispatch()`
        foreach ($coverage as $storeId => $keys) {
            $this->claimCoveredStore($storeId, $keys, $handOver);
        }

        foreach ($targets as $key => $target) {
            if ($handOver[$key]) {
                $this->handOver($target['classDefinitionId'], $target['storeIds']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function discard(): void
    {
        $this->noted = [];
    }

    public function reset(): void
    {
        $this->discard();
    }

    /**
     * Whether the key writes the notes describe are still inside a transaction, in which case no
     * answer this flush could give is about the state anyone else can read.
     *
     * A connection that cannot even be asked is answered with no rather than yes: the hand-over is
     * guarded and converges, where deferring forever is the one outcome that does not - there is no
     * later moment than the terminate the subscriber already flushes at.
     */
    private function isWriteUncommitted(): bool
    {
        try {
            return $this->connection->isTransactionActive();
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to read whether the classification store key writes are committed: %s',
                $e->getMessage()
            ));

            return false;
        }
    }

    /**
     * Which indices a store reaches is a read of every class definition there is, so the store's own
     * marker is asked first: while it answers, every class the store reaches has a remap queued
     * already and resolving them would only rediscover that.
     *
     * Resolving happens under the same guard as the hand-over, and against Throwable rather than
     * Exception, because the key row is committed by the time either runs: a store whose classes
     * cannot be resolved has to cost a log line, not the operator's edit turned into a 500.
     *
     * The class a remap is handed over for is read back as the value of its own entry, never as the
     * key: a class id made of digits is an integer array key, and the queue is handed the id as the
     * string the class carries it as. The entry is written whole rather than a member at a time, so
     * no state of `$targets` exists in which an entry is missing its `storeIds`.
     *
     * What `$coverage` records per store is the entries of `$targets` the store reaches, looked up
     * by class id the way they were written - never read back out as class ids, because a class id
     * made of digits is an integer array key by the time it is one. A store whose resolve did not
     * finish, and one that was skipped on its own marker, is recorded in neither: it may not be
     * claimed on a coverage nobody established.
     *
     * @param array<array-key, array{classDefinitionId: string, storeIds: list<int>}> $targets the
     *        classes collected so far and the noted stores reaching each of them
     * @param array<int, list<array-key>> $coverage the `$targets` entries each resolved store
     *        reaches, which is what a claim on that store has to cover in full
     */
    private function collectTargets(int $storeId, array &$targets, array &$coverage): void
    {
        try {
            if ($this->remapQueue->hasPendingStoreRemap($storeId)) {
                return;
            }

            $reached = [];

            foreach ($this->remapTargetResolver->resolveForStore($storeId) as $classDefinitionId) {
                $storeIds = $targets[$classDefinitionId]['storeIds'] ?? [];
                $storeIds[] = $storeId;

                $targets[$classDefinitionId] = [
                    'classDefinitionId' => $classDefinitionId,
                    'storeIds' => $storeIds,
                ];
                $reached[] = $classDefinitionId;
            }

            $coverage[$storeId] = $reached;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to resolve the search indices of the classification store "%d": %s',
                $storeId,
                $e->getMessage()
            ));
        }
    }

    /**
     * A remap already waiting in the queue is one this change has nothing to add to, which is what
     * keeps twenty flag changes of a store from remapping its classes twenty times. The two
     * statements are not held against each other: two requests racing hand one remap over each,
     * which costs one superfluous remap and never a missing one.
     *
     * Asked once per flush and the answer reused, because the claim on a store and the hand-overs
     * it stands for have to agree on exactly which classes this flush is handing over. Asked twice,
     * a class that became pending between the two answers would be claimed for by a message that
     * does not carry this store, and the store's marker would then outlive it.
     *
     * A queue that cannot be read is answered with yes rather than no: the hand-over has a guard of
     * its own, and the cost of attempting one that was not needed is a superfluous re-index, where
     * the cost of skipping one that was is the stale mapping.
     */
    private function isHandOverNeeded(string $classDefinitionId): bool
    {
        try {
            return !$this->remapQueue->hasPendingRemap($classDefinitionId);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to read the queued search index remaps of the class "%s": %s',
                $classDefinitionId,
                $e->getMessage()
            ));

            return true;
        }
    }

    /**
     * Claims the store only when every class it reaches is one this flush is handing over itself,
     * so that the marker standing for "all of them are pending" is written by someone who knows
     * that it is. A class another flush queued is covered by a message that does not carry this
     * store, and would be released out from under the claim while the claim still suppressed it.
     *
     * A store that reaches no class at all is not claimed either: there is no coverage to stand on,
     * and a class added to the store inside the window would have its flag changes skipped.
     *
     * Failing to claim costs the next flush one resolve of the store, so it is logged like the rest
     * and nothing more.
     *
     * @param list<array-key> $keys the `$targets` entries this store reaches
     * @param array<array-key, bool> $handOver whether this flush is handing each collected class over
     */
    private function claimCoveredStore(int $storeId, array $keys, array $handOver): void
    {
        if ($keys === []) {
            return;
        }

        foreach ($keys as $key) {
            if (!($handOver[$key] ?? false)) {
                return;
            }
        }

        try {
            $this->remapQueue->claimStoreCoverage($storeId);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to mark the search indices of the classification store "%d" as queued: %s',
                $storeId,
                $e->getMessage()
            ));
        }
    }

    /**
     * @param list<int> $storeIds
     */
    private function handOver(string $classDefinitionId, array $storeIds): void
    {
        try {
            $this->remapQueue->enqueueRemap($classDefinitionId, $storeIds);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to queue a search index remap for the class "%s": %s',
                $classDefinitionId,
                $e->getMessage()
            ));
        }
    }
}
