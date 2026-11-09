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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\QualifiedEntryShapeTrait;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_filter;
use function array_is_list;
use function array_values;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

/**
 * The slot itself: what the row holds at it, which of those entries the key's declaration shows,
 * and what a write stores back.
 *
 * Which of the two shapes a slot is in is the key declaration's answer and no value's
 * ({@see self::holdsEntries()}), so one reader serves every consumer of one cell
 * ({@see self::getDefaultSlotValue()}) whatever the key declares, and the list a read publishes is
 * the list the row holds.
 *
 * Two questions that are asked of a slot but not answered by it live beside this class rather than
 * in it: the read of what a write supplied ({@see QualifiedValueParserInterface}) and the engine's
 * verdict on what that write addresses ({@see EngineCapabilityGuardInterface}). Both are reached
 * through here, so a caller still has one place to ask.
 *
 * @internal
 */
final readonly class QualifiedValueSerializer implements QualifiedValueSerializerInterface
{
    use QualifiedEntryShapeTrait;

    /**
     * Every member an entry this bundle stored can carry, which is what tells its list from the
     * stored value of a key whose own type normalizes to a list of objects
     * ({@see self::isStoredEntry()}).
     */
    private const array STORED_ENTRY_MEMBERS = [
        self::VALUE_MEMBER,
        self::VARIANT_MEMBER,
        self::POSITION_MEMBER,
    ];

    public function __construct(
        private KeyQualifierResolverInterface $keyQualifierResolver,
        private QualifiedValueParserInterface $qualifiedValueParser,
        private EngineCapabilityGuardInterface $engineCapabilityGuard,
    ) {
    }

    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array
    {
        return $this->qualifiedValueParser->parseEntries($payload, $keyConfig);
    }

    public function assertSupported(array $values, ?array $capabilities): void
    {
        $this->engineCapabilityGuard->assertSupported($values, $capabilities);
    }

    public function readEntries(mixed $stored, KeyConfig $keyConfig): array
    {
        if (!$this->holdsEntries($stored, $keyConfig)) {
            return [new QualifiedValue($stored)];
        }

        return $this->readEntryList($stored);
    }

    public function getVisibleEntries(mixed $stored, KeyConfig $keyConfig): array
    {
        return array_values(array_filter(
            $this->readEntries($stored, $keyConfig),
            fn (QualifiedValue $value): bool => $this->isPermitted($value, $keyConfig)
        ));
    }

    public function getHiddenEntries(mixed $stored, KeyConfig $keyConfig): array
    {
        return array_values(array_filter(
            $this->readEntries($stored, $keyConfig),
            fn (QualifiedValue $value): bool => !$this->isPermitted($value, $keyConfig)
        ));
    }

    public function getDefaultSlotValue(mixed $stored, KeyConfig $keyConfig): mixed
    {
        return $this->getDefaultOf($this->getVisibleEntries($stored, $keyConfig));
    }

    public function mergeEntries(array $stored, array $supplied): array
    {
        $merged = [];
        foreach ($stored as $value) {
            $merged[$value->getQualifier()] = $value;
        }

        foreach ($supplied as $value) {
            $merged[$value->getQualifier()] = $value;
        }

        return array_values($merged);
    }

    public function toPayload(array $values): array
    {
        $payload = [];
        foreach ($values as $value) {
            $entry = [self::VALUE_MEMBER => $value->getValue()];

            if ($value->getVariant() !== null) {
                $entry[self::VARIANT_MEMBER] = $value->getVariant();
            }

            if ($value->getPosition() !== self::DEFAULT_POSITION) {
                $entry[self::POSITION_MEMBER] = $value->getPosition();
            }

            $payload[] = $entry;
        }

        return $payload;
    }

    /**
     * The declaration is asked first and settles it on its own for a key declaring no qualifier:
     * such a key holds one value of its own type, and no shape that value has makes it a list of
     * entries. That is the rule this ticket is built on, and asking the value instead is what made
     * a table row - `[['v' => '80'], ['v' => '81']]`, which
     * {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\TableAdapter} stores as the
     * value of a one-column table - read as two entries and reduced to `80`.
     *
     * For a *declaring* key the shape is then asked, and for one reading only: a slot of such a key
     * holds the entry list and nothing else once this bundle has written it
     * ({@see QualifiedValueSerializerInterface::toPayload()}), so the shape has to tell that list
     * from the single value the slot still holds where the key declared after the value was
     * written. That reading is strict - every element an entry, and an entry nothing but the
     * members this bundle writes - so that the value of a type whose normalized form is a list of
     * objects is not mistaken for it. {@see QualifiedValueParser::looksLikeEntryList()} reads a
     * *supplied* payload leniently, for the reason stated there.
     *
     * @phpstan-assert-if-true array<int, array<string, mixed>> $value
     */
    public function holdsEntries(mixed $value, KeyConfig $keyConfig): bool
    {
        if (!$this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            return false;
        }

        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        foreach ($value as $entry) {
            if (!$this->isStoredEntry($entry)) {
                return false;
            }
        }

        return true;
    }

    /**
     * One entry as this bundle stores it: an entry object carrying no member beyond the three it
     * writes. The extra demand over {@see QualifiedEntryShapeTrait::isEntry()} is what keeps the
     * stored value of a key's own type from being read as an entry where the declaration cannot
     * refuse it - a `quantityValue`'s `['value' => 30, 'unit' => 'cm']`, a relation descriptor's
     * `['type' => 'object', 'id' => 5521]` and a table row carrying any second column all answer no
     * here.
     *
     * It is asked of storage alone. At the request boundary an entry is "carries the value member"
     * and stays that, so a payload is refused by {@see QualifiedValueParser::parseEntry()} with the
     * reason it is refused for, rather than being silently read as a value.
     */
    private function isStoredEntry(mixed $entry): bool
    {
        if (!$this->isEntry($entry)) {
            return false;
        }

        foreach ($entry as $member => $ignored) {
            if (!in_array($member, self::STORED_ENTRY_MEMBERS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * One list of entry objects, read into the values it holds. A stored qualifier of the wrong
     * shape falls back to the default rather than failing the read: it is data this bundle wrote,
     * not a request.
     *
     * @param array<int, array<string, mixed>> $entries
     *
     * @return QualifiedValue[]
     */
    private function readEntryList(array $entries): array
    {
        $values = [];
        foreach ($entries as $entry) {
            $variant = $entry[self::VARIANT_MEMBER] ?? null;
            $position = $entry[self::POSITION_MEMBER] ?? self::DEFAULT_POSITION;

            $values[] = new QualifiedValue(
                $entry[self::VALUE_MEMBER],
                is_string($variant) && $variant !== '' ? $variant : null,
                is_int($position) && $position >= self::DEFAULT_POSITION ? $position : self::DEFAULT_POSITION
            );
        }

        return $values;
    }

    /**
     * The one value a list of entries shows where a consumer holds a single cell: the default
     * variant at the first position, the first entry where the list holds no such entry, and null
     * where the list is empty.
     *
     * @param QualifiedValue[] $values
     */
    private function getDefaultOf(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value->getVariant() === null && $value->getPosition() === self::DEFAULT_POSITION) {
                return $value->getValue();
            }
        }

        return $values === [] ? null : $values[0]->getValue();
    }

    /**
     * Whether the key's declaration permits the slot a value addresses: a declared variant - or the
     * default one - at the first position, or at any position where the key is repeatable. The
     * same two questions {@see QualifiedValueParser::assertDeclaredVariant()} and
     * {@see QualifiedValueParser::assertPositionPermitted()} put to a supplied entry, so a stored
     * value is shown under exactly the rule a written one is accepted under.
     */
    private function isPermitted(QualifiedValue $value, KeyConfig $keyConfig): bool
    {
        $declared = $this->keyQualifierResolver->getVariants($keyConfig) ?? [];
        $repeatable = $this->keyQualifierResolver->isMultiValue($keyConfig) === true;

        return ($value->getVariant() === null || in_array($value->getVariant(), $declared, true))
            && ($repeatable || $value->getPosition() === self::DEFAULT_POSITION);
    }
}
