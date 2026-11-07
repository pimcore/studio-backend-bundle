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

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;
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
 * A remap that cannot be handed over is logged and nothing more. The key row is committed by then,
 * and failing the request afterwards would cost the operator the edit rather than the convergence.
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
        $handedOver = [];

        foreach ($stores as $storeId) {
            foreach ($this->remapTargetResolver->resolveForStore($storeId) as $classDefinitionId) {
                if (isset($handedOver[$classDefinitionId])) {
                    continue;
                }

                $handedOver[$classDefinitionId] = true;
                $this->handOver($classDefinitionId);
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
     * A remap already waiting in the queue is one this change has nothing to add to, which is what
     * keeps twenty flag changes of a store from remapping its classes twenty times. The two
     * statements are not held against each other: two requests racing hand one remap over each,
     * which costs one superfluous remap and never a missing one.
     */
    private function handOver(string $classDefinitionId): void
    {
        try {
            if ($this->remapQueue->hasPendingRemap($classDefinitionId)) {
                return;
            }

            $this->remapQueue->enqueueRemap($classDefinitionId);
        } catch (Exception $e) {
            $this->logger->error(sprintf(
                'Failed to queue a search index remap for the class "%s": %s',
                $classDefinitionId,
                $e->getMessage()
            ));
        }
    }
}
