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

/**
 * @internal
 */
interface SearchIndexRemapTriggerInterface
{
    /**
     * Notes a key save of this store whose resolved search index flag differs between the two
     * definitions, and nothing at all for a save where it does not
     * ({@see SearchIndexFlagResolverInterface::isIndexed()}). Nothing reaches the queue here: an
     * apply writes thousands of keys and is one remap, which is what {@see self::flush()} is for.
     *
     * Both definitions are the key's `definition` column; null is a key that had none before the
     * save, which reads as indexed.
     */
    public function noteFlagChange(int $storeId, ?string $definitionBefore, ?string $definitionAfter): void;

    /**
     * Hands one remap to the queue per search index the noted stores reach, skipping an index a
     * remap is already queued and unprocessed for, and forgets the notes. A unit of work that noted
     * nothing hands over nothing.
     *
     * Hands nothing over, and forgets nothing, while the key writes the notes describe are still
     * inside a transaction: skipping an index on a remap that is pending now is a statement about
     * rows nobody outside that transaction can read, and the handler of that remap would release
     * the marker against the old row before the caller ever committed. The notes are kept for a
     * later call, which
     * {@see \Pimcore\Bundle\StudioBackendBundle\ClassificationStore\EventSubscriber\SearchIndexRemapFlushSubscriber}
     * makes once the request or the command is over.
     *
     * Call it wherever the writes are committed - every write surface in this bundle owns its
     * transaction and commits before it flushes, so the deferral only ever takes effect for a
     * caller that owns one instead.
     */
    public function flush(): void;

    /**
     * An opaque mark of the notes as they stand, to be read before a unit of work that may have to
     * roll its own key writes back and passed to {@see self::discardSince()} if it does.
     */
    public function mark(): int;

    /**
     * Forgets the notes taken since the mark without handing anything over, which is what a unit of
     * work that rolled ITS OWN key writes back leaves behind: there is no change to converge to,
     * and a note kept instead would be handed over by whatever flushes next.
     *
     * The notes that were already there when the mark was read are kept, including a note of a
     * store this unit of work went on to note again: they are about rows this unit of work neither
     * wrote nor rolled back, and dropping them leaves those rows committed with nothing pending and
     * nothing noted - the stale mapping this whole mechanism exists to prevent. Only a unit of work
     * that rolled back may call it at all: one that merely failed to commit, or that left the
     * decision to a transaction a caller owns, does not know that its rows are gone, and the
     * documented answer when nothing can see that is to hand the remap over anyway.
     */
    public function discardSince(int $mark): void;

    /**
     * Forgets every note without handing anything over, which only the end of the unit of work
     * itself may do: the request or the command is over, so a note still held has no later flush to
     * be handed over by and would otherwise reach the next message of a worker-mode runtime.
     */
    public function discard(): void;
}
