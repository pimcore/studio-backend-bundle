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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_is_list;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class QualifiedValueParser implements QualifiedValueParserInterface
{
    use QualifiedEntryShapeTrait;

    private const string DEFAULT_VARIANT_LABEL = 'the default variant';

    public function __construct(
        private KeyQualifierResolverInterface $keyQualifierResolver,
    ) {
    }

    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array
    {
        if ($payload === []) {
            // the empty entry list, which is what a key holding no value publishes - no entry, not one
            // entry holding the empty array
            return [];
        }

        $declared = $this->keyQualifierResolver->getVariants($keyConfig) ?? [];

        // a grid cell addresses one variant, so it sends its entry alone: an object carrying `v`
        if (is_array($payload) && !array_is_list($payload) && $this->isEntry($payload)) {
            $payload = [$payload];
        }

        if (!$this->looksLikeEntryList($payload)) {
            // an un-updated client sends the value itself, which is the default variant
            return [new QualifiedValue($payload)];
        }

        $values = [];
        $seen = [];

        foreach ($payload as $entry) {
            $value = $this->parseEntry($entry, $declared);
            $qualifier = $value->getQualifier();

            if (array_key_exists($qualifier, $seen)) {
                throw new InvalidArgumentException(
                    sprintf('Two values address %s.', $this->describeVariant($value->getVariant())),
                    errorKey: HttpResponseErrorKeys::DUPLICATE_VALUE_QUALIFIER->value
                );
            }

            $seen[$qualifier] = true;
            $values[] = $value;
        }

        return $values;
    }

    /**
     * Whether a supplied payload is meant as a list of qualified entries: a list holding at least
     * one entry object - an array carrying `v`. The array value of a multi-select or a relation
     * field holds none, so such a value keeps travelling whole.
     *
     * Read leniently on purpose, because this is the request boundary: a list mixing entries with
     * something else is taken as a list and then refused by {@see self::parseEntry()}, rather than
     * being quietly coerced into one value holding the whole malformed array.
     * Only a key of type `multiValue` reaches here.
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

    /**
     * Whether a supplied payload could be a list of entries at all: a non-empty list. The empty
     * array is no list of entries, so a slot holding one keeps travelling whole - the empty *entry*
     * list is answered before this is asked ({@see self::parseEntries()}).
     */
    private function isCandidateList(mixed $payload): bool
    {
        return is_array($payload) && $payload !== [] && array_is_list($payload);
    }

    /**
     * One supplied entry: its shape is held to the request's own, and the pair it addresses is held
     * to the key's declaration, whatever the row already holds.
     *
     * @param string[] $declared
     *
     * @throws InvalidArgumentException
     */
    private function parseEntry(mixed $entry, array $declared): QualifiedValue
    {
        if (!$this->isEntry($entry)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Every value of the key has to be an object carrying "%s".',
                    QualifiedValueSerializerInterface::VALUE_MEMBER
                )
            );
        }

        // a `p` an older client still sends is not read, so every entry addresses the slot's one value
        $value = new QualifiedValue(
            $entry[QualifiedValueSerializerInterface::VALUE_MEMBER],
            $this->readVariantName($entry[QualifiedValueSerializerInterface::VARIANT_MEMBER] ?? null)
        );

        $this->assertDeclaredVariant($value->getVariant(), $declared);

        return $value;
    }

    /**
     * The variant name an entry carries, as a name: the default variant where the member is absent
     * or null. A name of another type, and the empty one, are no name a key can declare and no slot
     * the store can hold - core refuses an empty variant - so they are refused here rather than
     * carried into the write.
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

    private function undeclaredVariant(): InvalidArgumentException
    {
        return new InvalidArgumentException(
            'The value names a variant the key does not declare.',
            errorKey: HttpResponseErrorKeys::UNDECLARED_VARIANT->value
        );
    }

    private function describeVariant(?string $variant): string
    {
        return $variant === null ? self::DEFAULT_VARIANT_LABEL : sprintf('the variant "%s"', $variant);
    }
}
