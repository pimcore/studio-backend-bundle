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
 * per apply and only once the writes they describe are committed; by {@see discard()}, which is
 * what a rolled back apply leaves behind instead; and by {@see reset()}: implementing ResetInterface
 * makes autoconfiguration tag the service `kernel.reset`, so a long-running worker starts each
 * message with no note of the one before it.
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
        $stores = array_keys($this->noted);
        // forgotten before the hand-over, so a failing one is not retried by the next flush
        $this->noted = [];
        $targets = [];

        foreach ($stores as $storeId) {
            $this->collectTargets($storeId, $targets);
        }

        foreach ($targets as $target) {
            $this->handOver($target['classDefinitionId'], $target['storeIds']);
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
     * @param array<array-key, array{classDefinitionId: string, storeIds: list<int>}> $targets the
     *        classes collected so far and the noted stores reaching each of them
     */
    private function collectTargets(int $storeId, array &$targets): void
    {
        try {
            if ($this->remapQueue->hasPendingStoreRemap($storeId)) {
                return;
            }

            foreach ($this->remapTargetResolver->resolveForStore($storeId) as $classDefinitionId) {
                $storeIds = $targets[$classDefinitionId]['storeIds'] ?? [];
                $storeIds[] = $storeId;

                $targets[$classDefinitionId] = [
                    'classDefinitionId' => $classDefinitionId,
                    'storeIds' => $storeIds,
                ];
            }
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
     * @param list<int> $storeIds
     */
    private function handOver(string $classDefinitionId, array $storeIds): void
    {
        try {
            if ($this->remapQueue->hasPendingRemap($classDefinitionId)) {
                return;
            }

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
