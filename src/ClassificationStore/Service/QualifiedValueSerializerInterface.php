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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * Reads and writes the qualified values of one (group, key, language) of a `multiValue` key.
 *
 * An ordered list of entries carries them in the object data payload, each holding the value under
 * `v` plus only the qualifiers it has: `var` for a variant other than the default and `p` for a
 * position other than 0. An absent `var` means the default variant and an absent `p` means position
 * 0, so an entry that carries neither addresses the slot core's single-value path addresses.
 *
 * **The declaration is the key's type.** Only a key of type `multiValue` declares qualifiers
 * ({@see KeyQualifierResolverInterface}); `variants` or `multiValue` in the blob of a key of any
 * other type declare nothing, and such a key holds one value of its own type that is never read as
 * a list of entries, whatever shape it has.
 *
 * The slots are core's: a `multiValue` key's values are stored through
 * {@see Classificationstore::setKeyValues()} and read through
 * {@see Classificationstore::getKeyValues()}, one value of the key's own type per slot. The list is
 * how the payload carries them, not how the row holds them.
 *
 * The two questions a write does not answer itself are owned beside it and reached through it: the
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
     * Reached through here so that a caller reading and writing one key has one place to ask.
     *
     * @return QualifiedValue[]
     *
     * @throws InvalidArgumentException
     */
    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array;

    /**
     * Refuses a write that needs a capability the store's engine does not report, fail-closed on a
     * capability set of null - {@see EngineCapabilityGuardInterface::assertSupported()}, which owns
     * that verdict and states it.
     *
     * Reached through here so that a caller reading and writing one key has one place to ask.
     *
     * @param QualifiedValue[] $values
     * @param string[]|null $capabilities
     *
     * @throws InvalidArgumentException
     */
    public function assertSupported(array $values, ?array $capabilities): void;

    /**
     * The values the store holds for the key in one language - the default variant first, then each
     * declared variant in the order the key declares it, each slot's values in position order - read
     * through core's `getKeyValues()`. A variant the store's engine cannot address holds nothing.
     *
     * @return QualifiedValue[]
     *
     * @throws DatabaseException
     */
    public function readStoredEntries(
        Classificationstore $store,
        int $groupId,
        int $keyId,
        string $language,
        KeyConfig $keyConfig
    ): array;

    /**
     * The one value a consumer holding a single cell shows for a payload list of entries: the entry
     * of the default variant at the first position, or the first entry where there is none, and null
     * where the list is empty or is no list.
     *
     * Reading the stored values through {@see self::readStoredEntries()} and {@see self::toPayload()}
     * and reducing that list here is how the export reaches the value the grid cell shows.
     */
    public function getDefaultSlotValue(mixed $payload): mixed;

    /**
     * The values a payload list of entries holds at one variant, in position order. A null variant is the
     * default variant alone: an entry of a named variant is never read as the default slot's.
     *
     * @return mixed[]
     */
    public function getVariantValues(mixed $payload, ?string $variant): array;

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
