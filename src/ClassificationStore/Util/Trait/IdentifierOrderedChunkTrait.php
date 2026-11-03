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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait;

use Pimcore\Model\Listing\AbstractListing;

/**
 * The paging a configuration transfer walks a store's entities in, so that neither side of it
 * holds a whole store at once.
 *
 * @internal
 */
trait IdentifierOrderedChunkTrait
{
    /**
     * The identifier is what the artifact sorts by; the id is carried alongside it because a row
     * that does not have an identifier yet would otherwise leave the paging without a total order.
     */
    private function applyIdentifierOrderedChunk(AbstractListing $listing, int $limit, int $offset): void
    {
        $listing->setOrderKey(['identifier', 'id']);
        $listing->setOrder('ASC');
        $listing->setLimit($limit);
        $listing->setOffset($offset);
    }

    /**
     * The paging an apply walks the target in: a cursor on the row id rather than an offset into an
     * ordering, because an apply writes while it walks. Adopting an identifier moves a row within
     * an identifier ordering, and enabling a key moves it out of the set a key walk covers - either
     * one shifts the rows an offset has not reached yet, and the row that was shifted past is one
     * the walk never sees. The id is neither rewritten nor part of any condition, so a cursor on it
     * is unaffected by both.
     */
    private function applyIdCursorChunk(AbstractListing $listing, int $limit): void
    {
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');
        $listing->setLimit($limit);
    }
}
