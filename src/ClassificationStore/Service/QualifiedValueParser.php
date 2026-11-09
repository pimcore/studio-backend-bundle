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
use function array_key_exists;
use function in_array;
use function is_int;
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

    /**
     * Whether a supplied payload is meant as a list of qualified entries: a list holding at least
     * one entry object - an array carrying `v`. The array value of a multi-select or a relation
     * field holds none, so such a value keeps travelling whole.
     *
     * Read leniently on purpose, because this is the request boundary: a list mixing entries with
     * something else is taken as a list and then refused by {@see self::parseEntry()}, rather than
     * being quietly coerced into one value holding the whole malformed array.
     * {@see QualifiedValueSerializerInterface::holdsEntries()}, which reads what is stored rather
     * than what was sent, is strict for the opposite reason.
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
                sprintf(
                    'Every value of the key has to be an object carrying "%s".',
                    QualifiedValueSerializerInterface::VALUE_MEMBER
                )
            );
        }

        $value = new QualifiedValue(
            $entry[QualifiedValueSerializerInterface::VALUE_MEMBER],
            $this->readVariantName($entry[QualifiedValueSerializerInterface::VARIANT_MEMBER] ?? null),
            $this->readPosition(
                $entry[QualifiedValueSerializerInterface::POSITION_MEMBER]
                    ?? QualifiedValueSerializerInterface::DEFAULT_POSITION
            )
        );

        $this->assertDeclaredVariant($value->getVariant(), $declared);
        $this->assertPositionPermitted($value->getPosition(), $repeatable);

        return $value;
    }

    /**
     * The variant name an entry carries, as a name: the default variant where the member is absent
     * or null. A name of another type, and the empty one, are no name a key can declare and no slot
     * a row can hold - {@see QualifiedValueSerializerInterface::readEntries()} reads a stored
     * variant of either into the default one - so they are refused here rather than carried into the
     * lookup of what the row holds.
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
        if (!is_int($position) || $position < QualifiedValueSerializerInterface::DEFAULT_POSITION) {
            throw new InvalidArgumentException(
                sprintf(
                    'The "%s" of a value has to be a non-negative integer.',
                    QualifiedValueSerializerInterface::POSITION_MEMBER
                )
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
        if ($repeatable || $position === QualifiedValueSerializerInterface::DEFAULT_POSITION) {
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

    private function describeVariant(?string $variant): string
    {
        return $variant === null ? self::DEFAULT_VARIANT_LABEL : sprintf('the variant "%s"', $variant);
    }
}
