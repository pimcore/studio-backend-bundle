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

/**
 * @internal
 */
interface SearchIndexRemapQueueInterface
{
    /**
     * Whether a remap of this class's index is queued and not processed yet, which is what a second
     * flag change has nothing to add to. This is the only answer a remap is skipped on.
     */
    public function hasPendingRemap(string $classDefinitionId): bool;

    /**
     * Whether every class this store reaches has a remap queued that will be released together
     * with this answer, in which case resolving the store to its classes would answer with classes
     * that are all pending anyway. It decides nothing {@see self::hasPendingRemap()} does not; it
     * only spares the caller a read of every class definition there is.
     *
     * It answers only for a store {@see self::claimStoreCoverage()} was called for, which is the
     * caller's statement that the coverage is complete - and only while the claim still carries the
     * definition generation the definitions are in now. A claim made against an earlier generation
     * is a statement about a set of classes that has since been changed by a definition write, and
     * answers for nothing ({@see self::invalidateStoreCoverage()}).
     */
    public function hasPendingStoreRemap(int $storeId): bool;

    /**
     * The generation the class, object brick and field collection definitions are in, which is what
     * a coverage claim is stamped with: it changes on every definition write and is compared for
     * equality alone, never ordered.
     *
     * Read it BEFORE resolving a store to its classes, and claim with the value you read. The claim
     * says "these are all the classes the store reaches", which is only true of the definitions as
     * they stood when that resolve began, and a definition write between the two has to leave the
     * claim answering for nothing. Re-reading it at claim time instead would leave the window
     * between the read and the write open, which is the whole race this stamp exists to close.
     *
     * @throws Exception
     */
    public function readDefinitionGeneration(): string;

    /**
     * Hands one remap of this class's index to the queue and returns; the mapping is rewritten and
     * the elements are re-indexed by whoever drains the queue, never here. The remap is marked
     * pending before the hand-over and the mark is taken back again when the hand-over fails,
     * along with the coverage claimed for the given stores - a store whose classes are not all
     * pending is a store that may not be skipped.
     *
     * @param list<int> $storeIds the stores whose flag change this remap answers for
     *
     * @throws Exception
     */
    public function enqueueRemap(string $classDefinitionId, array $storeIds): void;

    /**
     * States that every class this store reaches is now covered by a pending remap that carries
     * this store, which is what {@see self::hasPendingStoreRemap()} answers with afterwards. Only a
     * caller that has just handed every one of those classes over may say so: claimed on partial
     * coverage, it suppresses the flag changes of the classes it left out.
     *
     * Call it before the hand-overs it stands for are dispatched, for the same reason the class
     * marker is written before its own: any of them failing takes the claim back with it.
     *
     * The coverage it claims is complete for the classes there were when it was claimed, and for no
     * others - which is what `$definitionGeneration` records and
     * {@see self::invalidateStoreCoverage()} supersedes.
     *
     * @param string $definitionGeneration the generation read with
     *        {@see self::readDefinitionGeneration()} BEFORE the store was resolved to the classes
     *        this claim covers
     *
     * @throws Exception
     */
    public function claimStoreCoverage(int $storeId, string $definitionGeneration): void;

    /**
     * Supersedes every store's coverage claim, whoever claimed it, so that the next flush resolves
     * its stores to their classes again rather than skipping them on a claim that no longer
     * describes the classes they reach.
     *
     * Called when a class, object brick or field collection definition is written: a coverage claim
     * is a statement about the set of classes a store reached at the moment it was made, and that
     * set is exactly what such a write changes. It costs the next flush one resolve per store; the
     * claim outliving its set costs the mapping of the class that joined the store.
     *
     * It moves the definition generation on rather than deleting the claims, so that it also
     * supersedes a claim that is written AFTER it, out of a flush that resolved its classes before
     * it: deleting what is there cannot reach a row that does not exist yet, and that claim would
     * then stand for a set of classes this write has already changed.
     *
     * The class markers are left alone. Each of them stands for one class and one message, neither
     * of which a definition write invalidates.
     *
     * @throws Exception
     */
    public function invalidateStoreCoverage(): void;

    /**
     * Forgets the queued remap of this class's index, and the coverage claimed for the stores it
     * was handed over for, so that a flag change arriving afterwards is handed over rather than
     * taken for a duplicate of it. Called by the handler of the queued remap, which is the moment
     * it stops being pending, and by a hand-over that failed.
     *
     * The stores are released even when this class is not the only one their coverage rested on:
     * the remaining classes keep their own markers, so the next flush resolves the store again and
     * skips the ones that are still pending. Under-suppression costs a resolve; the claim standing
     * for a class that is no longer pending costs the stale mapping.
     *
     * @param list<int> $storeIds the stores the remap was handed over for
     *
     * @throws Exception
     */
    public function releasePendingRemap(string $classDefinitionId, array $storeIds): void;
}
