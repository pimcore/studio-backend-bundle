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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * The destructive half of a replacing apply: everything the target store holds, taken out before
 * the file's configuration is written in its place.
 *
 * It is what makes an import of a file from another store idempotent without matching identifiers.
 * The members get freshly generated identifiers ({@see ArtifactDuplicatorInterface}), so nothing in
 * the file can ever match a row that is already there - applying the same file twice would leave
 * two copies of every entry if the first set were not removed.
 *
 * @internal
 */
interface StoreMemberPurgerInterface
{
    /**
     * Reports every row of the store as `REMOVED` and, outside a dry run, deletes it.
     *
     * The report is complete in both modes and is the whole of what a dry run answers a replace
     * with: the entities first - sub groups, collections, groups, keys - and then the two relation
     * levels, which the database takes with their endpoints rather than this deleting them one by
     * one.
     *
     * Removing a group removes every stored value under it, on both storage engines, through the
     * cascade of the value tables; the report marks that ({@see ApplyReport::hasRemovedGroup()}) in
     * a dry run as well.
     *
     * A removal is the one change to a key the search index cannot be told about as a flag change -
     * the key is gone and a definition of null reads as indexed - so the deleting mode notes the
     * store's mapping as stale instead
     * ({@see SearchIndexRemapTriggerInterface::noteMappingStale()}). The note follows the
     * deletions, never the report: a dry run notes nothing.
     *
     * @throws ConflictException
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws NotFoundException
     */
    public function purge(ApplyContext $context): void;
}
