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
 *   true, which starts before the hand-over rather than at its end. It is written inside the same
 *   guard as the hand-over, so a marker whose hand-over then failed is not left behind by the
 *   failure that wrote it.
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
 * ## The store-scoped marker beside it
 *
 * It stands for one thing only: *every* class this store reaches has a remap pending that will
 * release this marker with it. All it spares a coalesced save is resolving the store to its
 * classes, which reads every class definition there is ({@see SearchIndexRemapTargetResolver}).
 *
 * That is why it is not written here, with the class marker, but claimed separately by
 * {@see claimStoreCoverage()} - and only by a caller that has just established complete coverage
 * ({@see SearchIndexRemapTrigger::flush()}). Written per class, as it was, it claimed the store on
 * the strength of whichever classes that one hand-over happened to carry: a store reaching classes
 * A and B, with A already queued for a different store, would hand B over, claim the store on B's
 * marker alone, and then suppress every later flag change of that store for as long as B was
 * pending - including the ones A was by then the only pending remap for, which its own handler had
 * already released. The shortcut skipped the class it never covered.
 *
 * It is released like the class marker, by {@see releasePendingRemap()}, which the handler calls
 * with the stores its message answers for. The first of the store's classes to be handled releases
 * it while the others are still pending, which only costs the next flush one resolve: under-
 * suppression converges, over-suppression is the stale mapping this whole mechanism exists to
 * prevent. Whether a remap is skipped is still the class marker's answer alone.
 *
 * And it is released a second way, by {@see releaseAllStoreCoverage()}, because "every class this
 * store reaches" is answered once and the answer is not stable. The classes a store reaches are a
 * read of every class definition there is, and through each of them of the object brick and field
 * collection definitions it uses ({@see SearchIndexRemapTargetResolver}), and any of those three
 * gaining a field of this store joins a class to the set without touching anything the marker can
 * see. A store claimed while it reached CAR alone would
 * then skip BIKE for as long as CAR's remap stayed pending - BIKE having meanwhile been saved with a
 * field of this store and finished the remap its own save triggered - and the next flag change
 * would leave BIKE's mapping stale, which is the one outcome the store marker may never produce. So
 * a definition write drops every claim, through {@see releaseAllStoreCoverage()} and the
 * `SearchIndexRemapStoreCoverageSubscriber` that calls it. Every claim, not the stores the saved
 * definition reaches: the write is itself what decides which stores those are, so the set to
 * invalidate cannot be read off either side of it.
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
        try {
            // inside the guard, so that a marker written and a hand-over that then did not happen
            // are the same failure and are taken back together
            $this->writeClassMarker($classDefinitionId);

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
    public function claimStoreCoverage(int $storeId): void
    {
        $this->settingsStoreResolver->set(
            (string) $storeId,
            // the time the coverage was claimed, so a claim nothing ever released stops answering
            // by itself rather than skipping this store for good
            time(),
            self::SETTINGS_STORE_TYPE,
            self::STORE_SETTINGS_STORE_SCOPE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function releaseAllStoreCoverage(): void
    {
        // the ids are a column of digits, which a driver may hand back either way; the delete takes
        // the id as the string the entry is keyed by, the way every other call here does
        /** @var list<int|string> $storeIds */
        $storeIds = $this->settingsStoreResolver->getIdsByScope(self::STORE_SETTINGS_STORE_SCOPE);

        foreach ($storeIds as $storeId) {
            $this->settingsStoreResolver->delete((string) $storeId, self::STORE_SETTINGS_STORE_SCOPE);
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

    private function writeClassMarker(string $classDefinitionId): void
    {
        $this->settingsStoreResolver->set(
            $classDefinitionId,
            // the hand-over time, so a marker the handler never got to stops answering by itself
            time(),
            self::SETTINGS_STORE_TYPE,
            self::SETTINGS_STORE_SCOPE
        );
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
