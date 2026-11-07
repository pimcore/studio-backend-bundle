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

use Pimcore\Bundle\StaticResolverBundle\Models\Tool\SettingsStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger\SearchIndexRemapMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Throwable;
use function time;

/**
 * Hands a search index remap of one data object class to the queue and answers whether one is
 * already waiting there. The mapping is rewritten and the elements are re-indexed by the handler of
 * the queued message ({@see SearchIndexRemapMessage}), never in the request that writes the key.
 *
 * The marker that says "queued" is this bundle's own, because the only thing a message in a
 * transport can be asked about is nothing: the rebuild marker of the generic data index bundle is
 * set when a rebuild starts being queued, which is after a worker has picked the message up, and
 * would leave every change made before that un-coalesced.
 *
 * ## The ordering contract
 *
 * Every rule below is a rule about one marker and the one message it stands for. A change to this
 * class that cannot be stated as one of them is the wrong change.
 *
 * - **Who writes it, and when.** {@see enqueueRemap()}, and before `dispatch()` - never after. The
 *   marker is the claim that a message is on its way, so it has to exist for the whole time that is
 *   true, which starts before the hand-over rather than at its end.
 * - **Who clears it.** The handler of the message, as the first thing it does, which is the moment
 *   the remap stops being pending
 *   ({@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Messenger\SearchIndexRemapHandler}).
 *   If that never runs - a transport that dropped the message, retries that ran out, a worker
 *   nobody started - nobody clears it, so the marker carries the second it was written and stops
 *   answering {@see PENDING_REMAP_SECONDS} later. That expiry is the only thing between a lost
 *   message and every later remap of its class being skipped for good with nothing to read anywhere.
 * - **A synchronous transport.** The handler runs inside `dispatch()`, so it clears the marker
 *   during the very call that wrote it. Because the write comes first, there is a marker there for
 *   it to clear, and `enqueueRemap()` returns with nothing pending - correctly, the remap has
 *   already been handed on. The reverse order would have the handler clear a marker that does not
 *   exist yet and the write land behind it, leaving a marker nothing will ever clear.
 * - **`dispatch()` throwing after the marker exists.** The markers are deleted again and the
 *   failure is re-thrown for {@see SearchIndexRemapTrigger} to log, so a hand-over that did not
 *   happen leaves nothing claiming that it did.
 * - **A marker with no message behind it** costs the remaps of its class that are asked for inside
 *   one window, and nothing beyond it. That is acceptable because the race it replaces is the
 *   opposite one: two requests racing here each hand a remap over and cost one superfluous
 *   re-index, which converges, where a leaked marker silently converges on the stale mapping.
 *
 * The store-scoped marker beside it obeys the same rules and decides nothing of its own: it is
 * written exactly when a message carrying that store is dispatched and cleared exactly when such a
 * message is handled, so it never outlives the class marker it was written with. All it spares a
 * coalesced save is resolving the store to its classes, which reads every class definition there is
 * ({@see SearchIndexRemapTargetResolver}); whether a remap is skipped is still the class marker's
 * answer alone.
 *
 * @internal
 */
final readonly class SearchIndexRemapQueue implements SearchIndexRemapQueueInterface
{
    /**
     * Own scope, so the id of the entry can be the data object class id itself without sharing a
     * name space with anything else stored per class.
     */
    private const string SETTINGS_STORE_SCOPE = 'studio_classificationstore_index_remap';

    /**
     * A scope of its own for the store-scoped markers, because the two id spaces would otherwise
     * share one: a class id may be made of digits alone and every store id is, so class "5" and
     * store 5 would be the same entry and each would answer for the other.
     */
    private const string STORE_SETTINGS_STORE_SCOPE = 'studio_classificationstore_index_remap_store';

    private const string SETTINGS_STORE_TYPE = 'int';

    /**
     * How long a marker is taken to mean "queued". It is removed by the handler, so this is only
     * ever reached when the message never got there - a transport that dropped it, retries that ran
     * out, a worker that was never started. Without it such a marker would suppress every later
     * remap of that class for good, with nothing to read anywhere: the silent no-op this whole
     * mechanism exists to prevent. Expiring it costs one superfluous remap at worst.
     */
    private const int PENDING_REMAP_SECONDS = 3600;

    /**
     * The delay a class save hands its own mapping update over with, so a worker picks the remap up
     * after the writing request has committed
     * ({@see \Pimcore\Bundle\GenericDataIndexBundle\EventSubscriber\ClassDefinitionIndexSubscriber}).
     */
    private const int DISPATCH_DELAY_MILLISECONDS = 2000;

    public function __construct(
        private MessageBusInterface $messageBus,
        private SettingsStoreResolverInterface $settingsStoreResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingRemap(string $classDefinitionId): bool
    {
        return $this->isPending($classDefinitionId, self::SETTINGS_STORE_SCOPE);
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingStoreRemap(int $storeId): bool
    {
        return $this->isPending((string) $storeId, self::STORE_SETTINGS_STORE_SCOPE);
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueRemap(string $classDefinitionId, array $storeIds): void
    {
        $this->writeMarkers($classDefinitionId, $storeIds);

        try {
            $this->messageBus->dispatch(
                new SearchIndexRemapMessage($classDefinitionId, $storeIds),
                [new DelayStamp(self::DISPATCH_DELAY_MILLISECONDS)]
            );
        } catch (Throwable $e) {
            $this->releasePendingRemap($classDefinitionId, $storeIds);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function releasePendingRemap(string $classDefinitionId, array $storeIds): void
    {
        $this->settingsStoreResolver->delete($classDefinitionId, self::SETTINGS_STORE_SCOPE);

        foreach ($storeIds as $storeId) {
            $this->settingsStoreResolver->delete((string) $storeId, self::STORE_SETTINGS_STORE_SCOPE);
        }
    }

    /**
     * @param list<int> $storeIds
     */
    private function writeMarkers(string $classDefinitionId, array $storeIds): void
    {
        // the hand-over time, so a marker the handler never got to stops answering by itself
        $handedOverAt = time();
        $this->settingsStoreResolver->set(
            $classDefinitionId,
            $handedOverAt,
            self::SETTINGS_STORE_TYPE,
            self::SETTINGS_STORE_SCOPE
        );

        foreach ($storeIds as $storeId) {
            $this->settingsStoreResolver->set(
                (string) $storeId,
                $handedOverAt,
                self::SETTINGS_STORE_TYPE,
                self::STORE_SETTINGS_STORE_SCOPE
            );
        }
    }

    private function isPending(string $id, string $scope): bool
    {
        $marker = $this->settingsStoreResolver->get($id, $scope);

        if ($marker === null) {
            return false;
        }

        return (int) $marker->getData() + self::PENDING_REMAP_SECONDS > time();
    }
}
