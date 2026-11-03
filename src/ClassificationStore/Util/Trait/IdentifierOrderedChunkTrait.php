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
     * The paging an export walks a store in: the identifier is what the artifact sorts by, and the
     * id is carried alongside it because a row that does not have an identifier yet would otherwise
     * leave the paging without a total order.
     *
     * A cursor on that pair rather than an offset into it. An export is not one read: it is a read
     * per page per section, and nothing stops a configuration write from committing between two of
     * them. Deleting a row the walk has already passed shifts every later row one place towards the
     * start, and an offset into the ordering then resumes past the row that moved - which leaves it
     * out of a download that reports success. The cursor names the last row it handed back instead
     * of counting how many it has seen, so a row that did not move is still reached.
     *
     * `$afterIdentifier` null is the first page. `COALESCE` keeps the comparison total for a row
     * with no identifier: NULL and `''` cannot both occur, because an identifier is validated
     * against a pattern that refuses the empty string.
     */
    private function applyIdentifierCursorChunk(
        AbstractListing $listing,
        int $limit,
        ?string $afterIdentifier,
        int $afterId,
    ): void {
        if ($afterIdentifier !== null) {
            $listing->addConditionParam(
                "(COALESCE(`identifier`, ''), `id`) > (:afterIdentifier, :afterIdentifierCursorId)",
                ['afterIdentifier' => $afterIdentifier, 'afterIdentifierCursorId' => $afterId]
            );
        }

        $listing->setOrderKey(['identifier', 'id']);
        $listing->setOrder('ASC');
        $listing->setLimit($limit);
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

    /**
     * Narrows an apply walk to the rows that carry an identifier, or to the rows that do not.
     *
     * An apply walks the target in those two phases rather than in one, because a row that carries
     * no identifier is matched by name and a row that carries one is matched by identifier - and the
     * name of an identifierless row may be the name of the entry whose identifier another row owns.
     * Walking the owners first is what lets the owner claim its entry before the namesake can: the
     * namesake then finds the entry taken and is left in place as target only, instead of being
     * adopted onto the wrong row while the owner is skipped.
     *
     * The two phases are disjoint, so the pair reads each row of the store exactly once between
     * them, and each phase keeps the id cursor above.
     *
     * A null phase is no condition at all, which is every row in one walk.
     */
    private function applyIdentifierPhase(AbstractListing $listing, ?bool $withIdentifier): void
    {
        if ($withIdentifier === null) {
            return;
        }

        $listing->addConditionParam($withIdentifier ? '`identifier` IS NOT NULL' : '`identifier` IS NULL');
    }
}
