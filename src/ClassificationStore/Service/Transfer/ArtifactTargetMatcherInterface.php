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
use Generator;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * One half of an apply: given an entry of the file, which existing configuration row it describes,
 * and whether that row already says what the entry says. Walking the target in chunks belongs here
 * too, because what is walked is what is matched.
 *
 * It decides nothing and writes nothing - every outcome is the reconciler's
 * ({@see ReconcilerInterface}).
 *
 * @internal
 */
interface ArtifactTargetMatcherInterface
{
    /**
     * The order an apply walks the target in: the rows that carry an identifier, then the rows that
     * do not.
     *
     * Identifier ownership has to be resolved across the whole level before a name may adopt
     * anything, because a name is not an identity - an identifierless key called `weight` and the
     * key that owns the file's `weight` entry can both exist. Walking the owners first lets the
     * owner claim its entry; the namesake then finds it taken and is left in place as target only,
     * instead of being adopted onto the wrong row while the owner is skipped.
     *
     * The phases are also the order in which nothing an apply writes moves a row backwards: stamping
     * an identifier onto an adopted row moves it into the phase that has already run.
     */
    public const array IDENTIFIER_PHASES = [true, false];

    /**
     * The entry of the section that describes the row, by its key in the section, or null where the
     * section describes no such row.
     *
     * The identifier first; the name only for a row that carries no identifier of its own, which is
     * the row an identifier is adopted onto. A name hit is only ever the right row because the
     * caller has walked the identifier owners first ({@see self::IDENTIFIER_PHASES}).
     *
     * @param array<string, array<string, mixed>> $entries
     * @param array<string, string> $entriesByName
     */
    public function matchEntryKey(
        array $entries,
        array $entriesByName,
        ?string $identifier,
        string $name,
    ): ?string;

    /**
     * The entries of a section by name, lowercased because the name columns are compared case
     * insensitively by the database the rows live in.
     *
     * @param array<string, array<string, mixed>> $entries
     *
     * @return array<string, string>
     */
    public function indexByName(array $entries, string $field): array;

    /**
     * The store's rows of one configuration level, a chunk at a time, taken after the id of the
     * last row handed back. `$walk` reads one chunk and is called with that id and the chunk size.
     *
     * The cursor rather than an offset: apply writes while it walks, and an offset into an
     * identifier ordering would skip the rows an adopted identifier moved past.
     */
    public function walkTargetByIdCursor(ApplyContext $context, Closure $walk): Generator;

    /**
     * The store's rows of one relation level, a chunk at a time, taken after the last row handed
     * back. `$walk` reads one chunk and is called with that row - null for the first chunk - and
     * the chunk size.
     *
     * A relation has no single row id to cursor on, so the cursor is the whole row: the identifier
     * pair its ordering is built from, with its primary key behind it. An offset would resume past
     * a row that a deletion elsewhere shifted towards the start.
     */
    public function walkTargetByRowCursor(ApplyContext $context, Closure $walk): Generator;

    /**
     * Whether the row's name and description are the ones the entry carries, which is everything a
     * sub group, a collection and a group hold of their own.
     */
    public function describesSameEntity(
        ?string $storedName,
        ?string $storedDescription,
        string $name,
        ?string $description,
    ): bool;

    /**
     * Whether the key row already says what the entry says, every column of it.
     */
    public function describesSameKey(KeyConfig $target, ArtifactKeyEntry $entry): bool;

    /**
     * Whether the assignment row already says what the entry says.
     */
    public function describesSameAssignment(KeyGroupRelation $target, ArtifactAssignmentEntry $entry): bool;

    /**
     * @param array<string, mixed> $entry
     */
    public function readNullableString(array $entry, string $field): ?string;
}
