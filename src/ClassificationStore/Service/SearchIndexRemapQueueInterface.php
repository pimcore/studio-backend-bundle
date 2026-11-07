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
     * caller's statement that the coverage is complete.
     */
    public function hasPendingStoreRemap(int $storeId): bool;

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
     * @throws Exception
     */
    public function claimStoreCoverage(int $storeId): void;

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
