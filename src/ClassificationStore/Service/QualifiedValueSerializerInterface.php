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
 * An ordered list of entries carries them, each holding the value under `v` plus only the
 * qualifiers it has: `var` for a variant other than the default and `p` for a position other than
 * 0. An absent `var` means the default variant and an absent `p` means position 0, so an entry that
 * carries neither addresses the slot core's single-value path addresses.
 *
 * **The key's declaration decides the shape, and nothing else decides it.** That is this ticket's
 * rule and it governs reading a stored slot exactly as it governs writing one: a key declaring
 * neither `variants` nor `multiValue` holds one value of its own type, and that value is never read
 * as a list of entries, whatever shape it happens to have. Asking the *value* instead cannot work,
 * because the entry members are not the bundle's to reserve: `[['v' => '80'], ['v' => '81']]` is
 * the list of two entries *and* the stored value of a table key whose one column is named `v`
 * ({@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\TableAdapter}), so a read that
 * sniffs it reduces that table to `80` for a key that declares nothing at all.
 *
 * The declaration decides what a write stores, too, which is what leaves the stored shape readable:
 * a declaring key's slot holds the entry list and only ever the entry list - the empty one included
 * - and a key declaring nothing holds the single value it has always held.
 *
 * The two questions a slot does not answer itself are owned beside it and reached through it: the
 * read of a supplied payload ({@see QualifiedValueParserInterface}) and the engine's verdict on what
 * a write addresses ({@see EngineCapabilityGuardInterface}).
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
     * The entries a write supplies, validated - {@see QualifiedValueParserInterface::parseEntries()},
     * which owns the rules a supplied payload is held to and states them.
     *
     * Reached through here so that a caller reading and writing one slot has one place to ask.
     *
     * @return QualifiedValue[]
     *
     * @throws InvalidArgumentException
     */
    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array;

    /**
     * Whether a stored slot is read as the entry list of a qualified slot. **The key's declaration
     * is asked first and refuses on its own:** a key declaring no qualifier holds one value, so its
     * slot is never read as entries however much the value looks like them.
     *
     * The shape is then asked of a *declaring* key's slot alone, and only to tell the entry list
     * from the single value that slot held before the key declared anything, which is the one
     * reading the declaration cannot settle.
     */
    public function holdsEntries(mixed $value, KeyConfig $keyConfig): bool;

    /**
     * Refuses a write that needs a capability the store's engine does not report, fail-closed on a
     * capability set of null - {@see EngineCapabilityGuardInterface::assertSupported()}, which owns
     * that verdict and states it.
     *
     * Reached through here so that a caller reading and writing one slot has one place to ask.
     *
     * @param QualifiedValue[] $values
     * @param string[]|null $capabilities
     *
     * @throws InvalidArgumentException
     */
    public function assertSupported(array $values, ?array $capabilities): void;

    /**
     * The entries behind a stored slot, read leniently: a slot that is not read as an entry list
     * ({@see self::holdsEntries()}) is the single entry of the default variant at position 0 - which
     * is every slot of a key declaring nothing, and the slot of a declaring key that still holds
     * the value it held before it declared.
     *
     * @return QualifiedValue[]
     */
    public function readEntries(mixed $stored, KeyConfig $keyConfig): array;

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
     * Every slot can be reduced through this, whatever the key declares, so the object payload, the
     * grid column, the export and the search preview cannot drift apart on which value they show.
     * The empty entry list of a declaring key reduces to null - the empty cell - and not to the
     * empty array it is stored as, which is a value of the key's own type and not a cell.
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
     * The entries as the object data payload carries them, and as the row holds them at the slot of
     * a declaring key: the published list and the stored list are one shape, so a client that saves
     * back what a read published writes the slot it read.
     *
     * The empty list is one of its values rather than nothing. A key whose every entry a write
     * cleared has no value, and that is a different state from a slot nothing was ever written to:
     * the key's configured default fills the second one on read
     * ({@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ClassificationStoreAdapter})
     * and must not fill the first, or a clearing request would be answered by the next read with a
     * value it never supplied. Telling the two apart needs no marker in the value, because the
     * declaration already says which of the two shapes the slot is in: `[]` at a declaring key's
     * slot is the empty entry list, and `[]` at the slot of a key declaring nothing is the empty
     * value of the key's own type, the multi-select selecting nothing included.
     *
     * @param QualifiedValue[] $values
     *
     * @return array<int, array<string, mixed>>
     */
    public function toPayload(array $values): array;
}
