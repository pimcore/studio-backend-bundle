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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\QualifiedEntryShapeTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use function array_filter;
use function array_is_list;
use function array_map;
use function array_merge;
use function array_values;
use function is_array;
use function is_int;
use function is_string;
use function usort;

/**
 * The qualified values of one `multiValue` key in one language: what core's store holds at its
 * slots, the list they travel as in the object data payload, and the one value a single cell shows.
 *
 * The slots themselves are core's: the store answers {@see Classificationstore::getKeyValues()} per
 * variant, and a write goes through {@see Classificationstore::setKeyValues()}. The list is only
 * how the payload carries them.
 *
 * Two questions that are asked of a write but not answered here live beside this class: the read of
 * what a write supplies ({@see QualifiedValueParserInterface}) and the engine's verdict on what
 * that write addresses ({@see EngineCapabilityGuardInterface}). Both are reached through here, so a
 * caller still has one place to ask.
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

    public function readStoredEntries(
        Classificationstore $store,
        int $groupId,
        int $keyId,
        string $language,
        KeyConfig $keyConfig
    ): array {
        $values = [];
        $variants = array_merge([null], $this->keyQualifierResolver->getVariants($keyConfig) ?? []);

        foreach ($variants as $variant) {
            try {
                $slot = $store->getKeyValues($groupId, $keyId, $language, $variant);
            } catch (UnsupportedEngineFeatureException) {
                // an engine that cannot address the variant holds nothing there.
                //
                // Only a NAMED variant ever reaches this: core asserts the engine's capability for a
                // variant other than the default one, and for a position above 0, and for nothing
                // else ({@see Classificationstore::getKeyValues()}), so the default slot - the one
                // slot every key has - is either read or it throws below. A read that could not
                // answer at all is therefore never published as a key holding nothing.
                continue;
            } catch (Exception $exception) {
                throw new DatabaseException($exception->getMessage(), previous: $exception);
            }

            foreach ($slot as $position => $value) {
                $values[] = new QualifiedValue($value, $variant, (int) $position);
            }
        }

        return $values;
    }

    public function getDefaultSlotValue(mixed $payload): mixed
    {
        if (!is_array($payload) || !array_is_list($payload)) {
            return null;
        }

        $values = $this->readPayload($payload);

        foreach ($values as $value) {
            if ($value->getVariant() === null && $value->getPosition() === self::DEFAULT_POSITION) {
                return $value->getValue();
            }
        }

        return $values === [] ? null : $values[0]->getValue();
    }

    public function getVariantValues(mixed $payload, ?string $variant): array
    {
        if (!is_array($payload) || !array_is_list($payload)) {
            return [];
        }

        $values = array_filter(
            $this->readPayload($payload),
            static fn (QualifiedValue $value): bool => $value->getVariant() === $variant
        );
        usort($values, static fn (QualifiedValue $a, QualifiedValue $b): int => $a->getPosition() <=> $b->getPosition());

        return array_map(static fn (QualifiedValue $value): mixed => $value->getValue(), $values);
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
     * The entries a payload list holds, the elements that are not entries left out.
     *
     * @param array<int, mixed> $payload
     *
     * @return QualifiedValue[]
     */
    private function readPayload(array $payload): array
    {
        $values = [];
        foreach ($payload as $entry) {
            if (!$this->isEntry($entry)) {
                continue;
            }

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
}
