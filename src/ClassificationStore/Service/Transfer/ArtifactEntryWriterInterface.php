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

use Closure;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The other half of an apply: one entry of the file against the row it was matched to - what that
 * is, created, updated, adopted, unchanged or refused, and the write it takes.
 *
 * Which rows there are to match is still the reconciler's ({@see ReconcilerInterface}) - it supplies
 * the walks a level is taken in - and what a row describes is the matcher's
 * ({@see ArtifactTargetMatcherInterface}).
 *
 * @internal
 */
interface ArtifactEntryWriterInterface
{
    /**
     * The entity sections that carry a name and a description only, which is sub groups,
     * collections and groups: the store's rows of the level are walked, each matched against the
     * section, and every entry the walk did not reach is created.
     *
     * `$walk` reads one chunk of rows, by cursor, and is called with the identifier phase
     * ({@see ArtifactTargetMatcherInterface::IDENTIFIER_PHASES}), the cursor and the chunk size;
     * `$save` writes one entry onto the row it was matched to, or onto a new row, and hands back
     * its id.
     *
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function reconcileNamedEntities(
        ApplyContext $context,
        ArtifactSection $section,
        ArtifactDocument $document,
        Closure $walk,
        Closure $save,
    ): void;

    /**
     * The rows of one level against the entries of its section: every row the walks reach claims the
     * entry it describes, and `$write` is called once with that row and that entry; afterwards
     * `$write` is called once per entry no row claimed, with null in the row's place. A row that
     * describes no entry, or one whose entry another row has claimed already, is reported by the
     * diagnostics and written over by nothing.
     *
     * `$walks` are the walks the level is taken in, in the order they have to be taken: each reads
     * one chunk of rows by cursor and is called with the cursor and the chunk size. A level takes
     * more than one because identifier ownership has to be resolved across it before a name may
     * adopt anything ({@see ArtifactTargetMatcherInterface::IDENTIFIER_PHASES}), and keys take two
     * more because a disabled row the file enables moves into the walk that follows.
     *
     * `$write` is handed the row - typed by the caller to the level it walks - and the entry.
     *
     * @param array<string, array<string, mixed>> $entries
     * @param list<Closure> $walks
     *
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function reconcileWalkedTargets(
        ApplyContext $context,
        ArtifactSection $section,
        array $entries,
        array $walks,
        Closure $write,
    ): void;

    /**
     * One key entry against the key row it was matched to, or against none.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function writeKey(ApplyContext $context, ?KeyConfig $target, array $entry): void;

    /**
     * One collection to group relation the target does not carry.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function createCollectionRelation(ApplyContext $context, string $entryKey, array $entry): void;

    /**
     * One key assignment against the relation row it was matched to, or against none.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function writeKeyAssignment(
        ApplyContext $context,
        ?KeyGroupRelation $target,
        array $entry,
        string $entryKey,
    ): void;
}
