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
     */
    public function flush(): void;
}
