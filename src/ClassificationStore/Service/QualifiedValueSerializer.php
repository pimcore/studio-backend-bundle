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
use function array_values;
use function in_array;
use function is_int;
use function is_string;

/**
 * The slot itself: what the row holds at it, which of those entries the key's declaration shows,
 * and what a write stores back.
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

    public function readEntries(mixed $stored): array
    {
        if (!$this->holdsEntries($stored)) {
            return [new QualifiedValue($stored)];
        }

        $values = [];
        foreach ($stored as $entry) {
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

    public function getVisibleEntries(mixed $stored, KeyConfig $keyConfig): array
    {
        return array_values(array_filter(
            $this->readEntries($stored),
            fn (QualifiedValue $value): bool => $this->isPermitted($value, $keyConfig)
        ));
    }

    public function getHiddenEntries(mixed $stored, KeyConfig $keyConfig): array
    {
        return array_values(array_filter(
            $this->readEntries($stored),
            fn (QualifiedValue $value): bool => !$this->isPermitted($value, $keyConfig)
        ));
    }

    public function getDefaultSlotValue(mixed $stored, KeyConfig $keyConfig): mixed
    {
        $values = $this->getVisibleEntries($stored, $keyConfig);

        foreach ($values as $value) {
            if ($value->getVariant() === null && $value->getPosition() === self::DEFAULT_POSITION) {
                return $value->getValue();
            }
        }

        return $values === [] ? null : $values[0]->getValue();
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
     * Strict, so that reading is deterministic and never drops anything. A qualified slot holds one
     * of two things - the list, or the bare value a single default entry stores
     * ({@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ClassificationStoreAdapter})
     * and every value written before the key declared anything - and an array that is neither is
     * read whole as that bare value rather than having its non-entry elements silently skipped.
     *
     * {@see QualifiedValueParser::looksLikeEntryList()} reads a *supplied* payload the other way
     * round.
     *
     * @phpstan-assert-if-true array<int, array<string, mixed>> $value
     */
    public function holdsEntries(mixed $value): bool
    {
        if (!$this->isCandidateList($value)) {
            return false;
        }

        foreach ($value as $entry) {
            if (!$this->isEntry($entry)) {
                return false;
            }
        }

        return true;
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
