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
     * Forgets the notes without handing anything over, which is what a rolled back unit of work
     * leaves behind: there is no change to converge to, and a note kept instead would be handed
     * over by whatever flushes next.
     */
    public function discard(): void;
}
