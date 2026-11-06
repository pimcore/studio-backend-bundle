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
     * flag change has nothing to add to.
     */
    public function hasPendingRemap(string $classDefinitionId): bool;

    /**
     * Hands one remap of this class's index to the queue and returns; the mapping is rewritten and
     * the elements are re-indexed by whoever drains the queue, never here.
     *
     * @throws Exception
     */
    public function enqueueRemap(string $classDefinitionId): void;

    /**
     * Forgets the queued remap of this class's index, so that a flag change arriving afterwards is
     * handed over rather than taken for a duplicate of it. Called by the handler of the queued
     * remap, which is the moment it stops being pending.
     *
     * @throws Exception
     */
    public function releasePendingRemap(string $classDefinitionId): void;
}
