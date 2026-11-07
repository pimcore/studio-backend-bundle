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
     * Whether every class a remap was handed over for alongside this store still has it queued, in
     * which case resolving the store to its classes would answer with classes that are all pending
     * anyway. It decides nothing {@see self::hasPendingRemap()} does not; it only spares the caller
     * a read of every class definition there is.
     */
    public function hasPendingStoreRemap(int $storeId): bool;

    /**
     * Hands one remap of this class's index to the queue and returns; the mapping is rewritten and
     * the elements are re-indexed by whoever drains the queue, never here. The remap is marked
     * pending before the hand-over, for this class and for each of the given stores, and the marks
     * are taken back again when the hand-over fails.
     *
     * @param list<int> $storeIds the stores whose flag change this remap answers for
     *
     * @throws Exception
     */
    public function enqueueRemap(string $classDefinitionId, array $storeIds): void;

    /**
     * Forgets the queued remap of this class's index and of the stores it was handed over for, so
     * that a flag change arriving afterwards is handed over rather than taken for a duplicate of
     * it. Called by the handler of the queued remap, which is the moment it stops being pending.
     *
     * @param list<int> $storeIds the stores the remap was handed over for
     *
     * @throws Exception
     */
    public function releasePendingRemap(string $classDefinitionId, array $storeIds): void;
}
