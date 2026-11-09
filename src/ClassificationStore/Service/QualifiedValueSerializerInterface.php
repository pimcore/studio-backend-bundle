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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * Reads and writes the qualified values of one (group, key, language) slot.
 *
 * The slot carries an ordered list of entries, each holding the value under `v` plus only the
 * qualifiers it has: `var` for a variant other than the default and `p` for a position other than
 * 0. An absent `var` means the default variant and an absent `p` means position 0, so an entry that
 * carries neither addresses the slot core's single-value path addresses.
 *
 * @internal
 */
interface QualifiedValueSerializerInterface
{
    public const string VALUE_MEMBER = 'v';

    public const string VARIANT_MEMBER = 'var';

    public const string POSITION_MEMBER = 'p';

    public const int DEFAULT_POSITION = 0;

    /**
     * The entries a write supplies, validated: the shape, the variant names against the key's
     * declaration and the uniqueness of the (variant, position) pairs.
     *
     * A payload that is not a list of qualified entries is one entry of the default variant at
     * position 0, which is what an un-updated client sends. A list holding at least one entry
     * object - an array carrying `v` - is a list of entries; the array value of a multi-select or a
     * relation field is not, and such a value keeps travelling whole.
     *
     * Every entry is held to the declaration, whatever the row already holds: a stored value the
     * declaration no longer permits is hidden by {@see self::getVisibleEntries()}, so a client
     * saving back what a read handed it never names one.
     *
     * @return QualifiedValue[]
     *
     * @throws InvalidArgumentException
     */
    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array;

    /**
     * Whether a value is the entry list of a qualified slot: a list of which *every* element is an
     * entry object.
     *
     * Which way a stored slot is *read*, never which shape the key serializes in - the declaration
     * alone decides that. A key that stops declaring keeps the entries it has stored, hidden, and a
     * write has to find them in order to leave them where they are.
     */
    public function holdsEntries(mixed $value): bool;

    /**
     * Refuses a write that needs a capability the store's engine does not report.
     *
     * Fail-closed: a capability set of null is a core that could not be asked, and a capability
     * that could not be confirmed is one the write does not get - it counts the same here as the
     * empty set an engine reporting none answers with
     * ({@see StoreEngineResolverInterface::getCapabilitiesOfStore()}). The two stay separate answers
     * where they are reported rather than acted on.
     *
     * Either way only a write that actually needs a capability is refused: one entry carrying no
     * variant at position 0 needs neither and goes through against any answer.
     *
     * @param QualifiedValue[] $values
     * @param string[]|null $capabilities
     *
     * @throws InvalidArgumentException
     */
    public function assertSupported(array $values, ?array $capabilities): void;

    /**
     * The entries behind a stored or already normalized slot, read leniently: a value stored before
     * the key declared anything is the single entry of the default variant at position 0.
     *
     * @return QualifiedValue[]
     */
    public function readEntries(mixed $stored): array;

    /**
     * The entries of the slot the key's declaration permits, in stored order: for a key declaring
     * nothing that is the entry of the default variant at the first position alone.
     *
     * @return QualifiedValue[]
     */
    public function getVisibleEntries(mixed $stored, KeyConfig $keyConfig): array;

    /**
     * The entries the row holds at the slot that the declaration does not permit. Narrowing a
     * declaration hides them and deletes nothing, so a write carries them over as they are.
     *
     * @return QualifiedValue[]
     */
    public function getHiddenEntries(mixed $stored, KeyConfig $keyConfig): array;

    /**
     * The one value a consumer holding a single cell shows for the slot: the visible entry of the
     * default variant at the first position, or the first visible entry where there is none, and
     * null where nothing is visible. A key declaring nothing shows its default entry alone.
     *
     * The bare value a single default entry stores is that entry, so a consumer of one cell per key
     * can reduce every slot through this. Named here rather than resolved twice, so the grid
     * column, the export and the search preview cannot drift apart on which value they show.
     */
    public function getDefaultSlotValue(mixed $stored, KeyConfig $keyConfig): mixed;

    /**
     * The stored entries with the supplied ones written over them, matched by (variant, position)
     * and never by position in the list, so a write addressing one slot leaves the rest of the
     * key's list intact. The stored order is kept and an entry the write adds is appended.
     *
     * @param QualifiedValue[] $stored
     * @param QualifiedValue[] $supplied
     *
     * @return QualifiedValue[]
     */
    public function mergeEntries(array $stored, array $supplied): array;

    /**
     * The entries as the object data payload carries them.
     *
     * @param QualifiedValue[] $values
     *
     * @return array<int, array<string, mixed>>
     */
    public function toPayload(array $values): array;
}
