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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_values;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class QualifiedValueSerializer implements QualifiedValueSerializerInterface
{
    private const string DEFAULT_VARIANT_LABEL = 'the default variant';

    public function __construct(
        private KeyQualifierResolverInterface $keyQualifierResolver,
    ) {
    }

    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array
    {
        if (!$this->looksLikeEntryList($payload)) {
            // an un-updated client sends the value itself, which is the default variant at position 0
            return [new QualifiedValue($payload)];
        }

        $declared = $this->keyQualifierResolver->getVariants($keyConfig) ?? [];
        $repeatable = $this->keyQualifierResolver->isMultiValue($keyConfig) === true;
        $values = [];
        $seen = [];

        foreach ($payload as $entry) {
            $value = $this->parseEntry($entry, $declared, $repeatable);
            $qualifier = $value->getQualifier();

            if (array_key_exists($qualifier, $seen)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Two values address %s at position %d.',
                        $this->describeVariant($value->getVariant()),
                        $value->getPosition()
                    ),
                    errorKey: HttpResponseErrorKeys::DUPLICATE_VALUE_QUALIFIER->value
                );
            }

            $seen[$qualifier] = true;
            $values[] = $value;
        }

        return $values;
    }

    public function assertSupported(array $values, ?array $capabilities): void
    {
        // a capability that could not be confirmed is one the write does not get
        $reported = $capabilities ?? [];

        $this->assertFeature(
            $this->needsVariants($values),
            StoreEngineResolverInterface::FEATURE_VARIANTS,
            $reported,
            'a variant other than the default one'
        );

        $this->assertFeature(
            $this->needsMultiValue($values),
            StoreEngineResolverInterface::FEATURE_MULTI_VALUE,
            $reported,
            'more than one value per variant'
        );
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
     * {@see self::looksLikeEntryList()} reads a *supplied* payload the other way round.
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
     * Whether a supplied payload is meant as a list of qualified entries: a list holding at least
     * one entry object - an array carrying `v`. The array value of a multi-select or a relation
     * field holds none, so such a value keeps travelling whole.
     *
     * Read leniently on purpose, because this is the request boundary: a list mixing entries with
     * something else is taken as a list and then refused by {@see self::parseEntry()}, rather than
     * being quietly coerced into one value holding the whole malformed array.
     * {@see self::holdsEntries()}, which reads what is stored rather than what was sent, is strict
     * for the opposite reason.
     */
    private function looksLikeEntryList(mixed $payload): bool
    {
        if (!$this->isCandidateList($payload)) {
            return false;
        }

        foreach ($payload as $entry) {
            if ($this->isEntry($entry)) {
                return true;
            }
        }

        return false;
    }

    private function isCandidateList(mixed $payload): bool
    {
        return is_array($payload) && $payload !== [] && array_is_list($payload);
    }

    private function isEntry(mixed $entry): bool
    {
        return is_array($entry) && array_key_exists(self::VALUE_MEMBER, $entry);
    }

    /**
     * One supplied entry: its shape is held to the request's own, and the pair it addresses is held
     * to the key's declaration, whatever the row already holds.
     *
     * @param string[] $declared
     *
     * @throws InvalidArgumentException
     */
    private function parseEntry(mixed $entry, array $declared, bool $repeatable): QualifiedValue
    {
        if (!$this->isEntry($entry)) {
            throw new InvalidArgumentException(
                sprintf('Every value of the key has to be an object carrying "%s".', self::VALUE_MEMBER)
            );
        }

        $value = new QualifiedValue(
            $entry[self::VALUE_MEMBER],
            $this->readVariantName($entry[self::VARIANT_MEMBER] ?? null),
            $this->readPosition($entry[self::POSITION_MEMBER] ?? self::DEFAULT_POSITION)
        );

        $this->assertDeclaredVariant($value->getVariant(), $declared);
        $this->assertPositionPermitted($value->getPosition(), $repeatable);

        return $value;
    }

    /**
     * The variant name an entry carries, as a name: the default variant where the member is absent
     * or null. A name of another type, and the empty one, are no name a key can declare and no slot
     * a row can hold - {@see self::readEntries()} reads a stored variant of either into the default
     * one - so they are refused here rather than carried into the lookup of what the row holds.
     *
     * @throws InvalidArgumentException
     */
    private function readVariantName(mixed $variant): ?string
    {
        if ($variant === null) {
            return null;
        }

        if (!is_string($variant) || $variant === '') {
            throw $this->undeclaredVariant();
        }

        return $variant;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function readPosition(mixed $position): int
    {
        if (!is_int($position) || $position < self::DEFAULT_POSITION) {
            throw new InvalidArgumentException(
                sprintf('The "%s" of a value has to be a non-negative integer.', self::POSITION_MEMBER)
            );
        }

        return $position;
    }

    /**
     * @param string[] $declared
     *
     * @throws InvalidArgumentException
     */
    private function assertDeclaredVariant(?string $variant, array $declared): void
    {
        if ($variant === null || in_array($variant, $declared, true)) {
            return;
        }

        throw $this->undeclaredVariant();
    }

    /**
     * The position a value addresses, against the key's own `multiValue` setting - the sibling of
     * {@see self::assertDeclaredVariant()}, which holds a variant to the names the key declares. A
     * key that does not declare itself repeatable holds one value per variant, so a position other
     * than the first is a qualifier it does not permit and is refused rather than silently stored.
     *
     * @throws InvalidArgumentException
     */
    private function assertPositionPermitted(int $position, bool $repeatable): void
    {
        if ($repeatable || $position === self::DEFAULT_POSITION) {
            return;
        }

        throw new InvalidArgumentException(
            'The key does not declare that it holds more than one value per variant.',
            errorKey: HttpResponseErrorKeys::KEY_NOT_REPEATABLE->value
        );
    }

    private function undeclaredVariant(): InvalidArgumentException
    {
        return new InvalidArgumentException(
            'The value names a variant the key does not declare.',
            errorKey: HttpResponseErrorKeys::UNDECLARED_VARIANT->value
        );
    }

    /**
     * Whether the key's declaration permits the slot a value addresses: a declared variant - or the
     * default one - at the first position, or at any position where the key is repeatable. The
     * same two questions {@see self::assertDeclaredVariant()} and
     * {@see self::assertPositionPermitted()} put to a supplied entry, so a stored value is shown
     * under exactly the rule a written one is accepted under.
     */
    private function isPermitted(QualifiedValue $value, KeyConfig $keyConfig): bool
    {
        $declared = $this->keyQualifierResolver->getVariants($keyConfig) ?? [];
        $repeatable = $this->keyQualifierResolver->isMultiValue($keyConfig) === true;

        return ($value->getVariant() === null || in_array($value->getVariant(), $declared, true))
            && ($repeatable || $value->getPosition() === self::DEFAULT_POSITION);
    }

    /**
     * @param string[] $capabilities
     *
     * @throws InvalidArgumentException
     */
    private function assertFeature(bool $needed, string $feature, array $capabilities, string $what): void
    {
        if (!$needed || in_array($feature, $capabilities, true)) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf('The engine of the key\'s store does not support %s.', $what),
            errorKey: HttpResponseErrorKeys::ENGINE_CAPABILITY_MISSING->value
        );
    }

    /**
     * @param QualifiedValue[] $values
     */
    private function needsVariants(array $values): bool
    {
        foreach ($values as $value) {
            if ($value->getVariant() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * A position other than the first, which is what a repeatable key holds a second value at.
     * Two values of two different variants both sit at position 0 and need `Variants` instead, so
     * the length of the list is not what decides here.
     *
     * @param QualifiedValue[] $values
     */
    private function needsMultiValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value->getPosition() !== self::DEFAULT_POSITION) {
                return true;
            }
        }

        return false;
    }

    private function describeVariant(?string $variant): string
    {
        return $variant === null ? self::DEFAULT_VARIANT_LABEL : sprintf('the variant "%s"', $variant);
    }
}
