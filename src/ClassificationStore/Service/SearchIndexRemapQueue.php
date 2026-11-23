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
use function bin2hex;
use function explode;
use function random_bytes;
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
 * And it is superseded a second way, by {@see invalidateStoreCoverage()}, because "every class this
 * store reaches" is answered once and the answer is not stable. The classes a store reaches are a
 * read of every class definition there is, and through each of them of the object brick and field
 * collection definitions it uses ({@see SearchIndexRemapTargetResolver}), and any of those three
 * gaining a field of this store joins a class to the set without touching anything the marker can
 * see. A store claimed while it reached CAR alone would
 * then skip BIKE for as long as CAR's remap stayed pending - BIKE having meanwhile been saved with a
 * field of this store and finished the remap its own save triggered - and the next flag change
 * would leave BIKE's mapping stale, which is the one outcome the store marker may never produce. So
 * a definition write supersedes every claim, through {@see invalidateStoreCoverage()} and the
 * `SearchIndexRemapStoreCoverageSubscriber` that calls it. Every claim, not the stores the saved
 * definition reaches: the write is itself what decides which stores those are, so the set to
 * invalidate cannot be read off either side of it.
 *
 * ## Why that is a generation and not a deletion
 *
 * A claim is not written at the moment its classes are read. The flush reads the store's classes,
 * decides every one of them, and only then claims ({@see SearchIndexRemapTrigger::flush()}), so a
 * definition write can land in between - and deleting the claims there reaches nothing, because the
 * claim is written after the deletion and survives it, standing for a set of classes the write has
 * already changed. That is the same stale coverage, through a window a deletion cannot close.
 *
 * So the claims are not deleted but stamped. {@see readDefinitionGeneration()} answers with the
 * generation the definitions are in; a claim carries the generation that was read BEFORE its
 * classes were resolved; and {@see hasPendingStoreRemap()} answers only while the two are still the
 * same. A definition write moves the generation on, which supersedes every claim carrying the old
 * one - the ones already written and the ones still to be written out of a resolve that began
 * before it alike. There is no compare-then-write anywhere on that path, so there is no window: the
 * claim is decided when it is READ, against a generation that cannot have been overtaken without
 * having changed.
 *
 * The generation is a fresh random value per write rather than a counter, because a counter is a
 * read-modify-write: two definition writes racing would both read n and both write n + 1, and a
 * claim stamped n + 1 by a flush that resolved between them would outlive the write that did not
 * get to move the generation past it. Nothing orders generations - they are only ever compared for
 * equality - so uniqueness per write is the whole requirement.
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

    /**
     * A third scope for the one entry the definition generation lives in. It is not per store: a
     * definition write changes which classes any store reaches, so there is one answer for all of
     * them, and sharing a scope with the per-store claims would make it a store id.
     */
    private const string GENERATION_SETTINGS_STORE_SCOPE = 'studio_classificationstore_index_remap_generation';

    private const string GENERATION_ID = 'definitions';

    /**
     * What the generation reads as while no definition write has been seen. A value of its own
     * rather than the empty string, so that a claim an earlier build wrote - before a claim carried
     * a generation at all - cannot match it and is resolved again rather than trusted.
     */
    private const string INITIAL_GENERATION = 'initial';

    /**
     * How many random bytes a generation is made of. Nothing orders them, so the only requirement
     * is that two definition writes cannot land on the same value.
     */
    private const int GENERATION_BYTES = 8;

    /**
     * What separates the second a coverage was claimed from the generation it was claimed against,
     * inside the one value the claim is stored as. The second comes first and the generation is the
     * remainder, so a generation is never parsed for and cannot be split by its own content.
     */
    private const string CLAIM_SEPARATOR = ':';

    private const string SETTINGS_STORE_TYPE = 'int';

    /**
     * A claim carries two things in one value and a generation is a token, so both are stored as
     * strings where a class marker is an int.
     */
    private const string SETTINGS_STORE_STRING_TYPE = 'string';

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
        $marker = $this->settingsStoreResolver->get($classDefinitionId, self::SETTINGS_STORE_SCOPE);

        if ($marker === null) {
            return false;
        }

        return $this->isWithinTheWindow((int) $marker->getData());
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingStoreRemap(int $storeId): bool
    {
        $claim = $this->settingsStoreResolver->get((string) $storeId, self::STORE_SETTINGS_STORE_SCOPE);

        if ($claim === null) {
            return false;
        }

        [$claimedAt, $claimedGeneration] = $this->readClaim((string) $claim->getData());

        // the generation is asked for as it stands now, where the claim carries the one its classes
        // were read in: a definition write between the two is what makes them differ
        if ($claimedGeneration !== $this->readDefinitionGeneration()) {
            return false;
        }

        return $this->isWithinTheWindow($claimedAt);
    }

    /**
     * {@inheritdoc}
     */
    public function readDefinitionGeneration(): string
    {
        $generation = $this->settingsStoreResolver->get(
            self::GENERATION_ID,
            self::GENERATION_SETTINGS_STORE_SCOPE
        );

        if ($generation === null) {
            return self::INITIAL_GENERATION;
        }

        return (string) $generation->getData();
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
    public function claimStoreCoverage(int $storeId, string $definitionGeneration): void
    {
        $this->settingsStoreResolver->set(
            (string) $storeId,
            // the time the coverage was claimed, so a claim nothing ever released stops answering
            // by itself rather than skipping this store for good - and the generation its classes
            // were read in, so a definition write that has landed since answers it away
            time() . self::CLAIM_SEPARATOR . $definitionGeneration,
            self::SETTINGS_STORE_STRING_TYPE,
            self::STORE_SETTINGS_STORE_SCOPE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateStoreCoverage(): void
    {
        $this->settingsStoreResolver->set(
            self::GENERATION_ID,
            bin2hex(random_bytes(self::GENERATION_BYTES)),
            self::SETTINGS_STORE_STRING_TYPE,
            self::GENERATION_SETTINGS_STORE_SCOPE
        );
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

    /**
     * The second a coverage claim was made and the generation it was made against.
     *
     * A value carrying no generation is one an earlier build wrote, before a claim recorded what it
     * was claimed against: nothing can say whether it still describes the classes its store
     * reaches, so it is answered with null, which no generation ever equals.
     *
     * @return array{0: int, 1: string|null}
     */
    private function readClaim(string $claim): array
    {
        $members = explode(self::CLAIM_SEPARATOR, $claim, 2);

        return [(int) $members[0], $members[1] ?? null];
    }

    private function isWithinTheWindow(int $writtenAt): bool
    {
        return $writtenAt + self::PENDING_REMAP_SECONDS > time();
    }
}
