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

use const JSON_THROW_ON_ERROR;
use JsonException;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function sprintf;

/**
 * @internal
 */
final readonly class ArtifactParser implements ArtifactParserInterface
{
    /**
     * The endpoints a relation section names its two sides by, per section.
     */
    private const array RELATION_ENDPOINTS = [
        ArtifactSection::COLLECTION_GROUP_RELATIONS->value => [
            ArtifactField::COLLECTION->value,
            ArtifactField::GROUP->value,
        ],
        ArtifactSection::KEY_GROUP_RELATIONS->value => [
            ArtifactField::GROUP->value,
            ArtifactField::KEY->value,
        ],
    ];

    private const string REQUIRED_STRING = 'a non empty string';

    private const string OPTIONAL_STRING = 'a string or null';

    private const string OPTIONAL_INT = 'an integer or null';

    private const string OPTIONAL_BOOL = 'a boolean or null';

    private const string OPTIONAL_OBJECT = 'an object or null';

    /**
     * Every field an entry of each section may carry, and the shape it has to arrive in. The whole
     * table is checked while the file is read, because the parser is the only gate in front of
     * apply: a field left out of it is a field a wrongly shaped file reaches a write with, where
     * the value is no longer a refusal but a change - `"enabled": "true"` silently disabling a key,
     * or `"name": 17` renaming a group to "17".
     *
     * Absent and null are the same thing for every optional field, which is what the artifact
     * writes for a column that carries nothing. The one place the two are told apart is the three
     * per assignment columns, and that is decided on the key being present rather than on its
     * shape ({@see self::readAssignmentEntry()}).
     */
    private const array ENTRY_SHAPE = [
        ArtifactSection::STORE->value => [
            ArtifactField::IDENTIFIER->value => self::REQUIRED_STRING,
            ArtifactField::NAME->value => self::REQUIRED_STRING,
            ArtifactField::DESCRIPTION->value => self::OPTIONAL_STRING,
        ],
        ArtifactSection::SUB_GROUPS->value => [
            ArtifactField::IDENTIFIER->value => self::REQUIRED_STRING,
            ArtifactField::NAME->value => self::REQUIRED_STRING,
            ArtifactField::DESCRIPTION->value => self::OPTIONAL_STRING,
        ],
        ArtifactSection::COLLECTIONS->value => [
            ArtifactField::IDENTIFIER->value => self::REQUIRED_STRING,
            ArtifactField::NAME->value => self::REQUIRED_STRING,
            ArtifactField::DESCRIPTION->value => self::OPTIONAL_STRING,
        ],
        ArtifactSection::GROUPS->value => [
            ArtifactField::IDENTIFIER->value => self::REQUIRED_STRING,
            ArtifactField::NAME->value => self::REQUIRED_STRING,
            ArtifactField::DESCRIPTION->value => self::OPTIONAL_STRING,
        ],
        ArtifactSection::KEYS->value => [
            ArtifactField::IDENTIFIER->value => self::REQUIRED_STRING,
            ArtifactField::NAME->value => self::REQUIRED_STRING,
            ArtifactField::TITLE->value => self::OPTIONAL_STRING,
            ArtifactField::DESCRIPTION->value => self::OPTIONAL_STRING,
            ArtifactField::TYPE->value => self::REQUIRED_STRING,
            ArtifactField::DEFINITION->value => self::OPTIONAL_OBJECT,
            ArtifactField::ENABLED->value => self::OPTIONAL_BOOL,
            ArtifactField::LOCALIZED->value => self::OPTIONAL_BOOL,
        ],
        ArtifactSection::COLLECTION_GROUP_RELATIONS->value => [
            ArtifactField::COLLECTION->value => self::REQUIRED_STRING,
            ArtifactField::GROUP->value => self::REQUIRED_STRING,
            ArtifactField::SORTER->value => self::OPTIONAL_INT,
        ],
        ArtifactSection::KEY_GROUP_RELATIONS->value => [
            ArtifactField::GROUP->value => self::REQUIRED_STRING,
            ArtifactField::KEY->value => self::REQUIRED_STRING,
            ArtifactField::SORTER->value => self::OPTIONAL_INT,
            ArtifactField::MANDATORY->value => self::OPTIONAL_BOOL,
            ArtifactField::METADATA->value => self::OPTIONAL_OBJECT,
            ArtifactField::DEFINITION_OVERRIDE->value => self::OPTIONAL_OBJECT,
            ArtifactField::SUB_GROUP->value => self::OPTIONAL_STRING,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function parse(string $json): ArtifactDocument
    {
        try {
            $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Failed to parse the configuration file: ' . $e->getMessage(), $e);
        }

        if (!is_array($document)) {
            throw new InvalidArgumentException('The configuration file does not carry an artifact.');
        }

        $store = $this->readStoreEntry($document);
        $sections = [];

        foreach (ArtifactSection::cases() as $section) {
            if ($section === ArtifactSection::STORE) {
                continue;
            }

            $sections[$section->value] = $this->readSection($document, $section);
        }

        return new ArtifactDocument(
            $this->requireString($store, ArtifactField::IDENTIFIER->value, ArtifactSection::STORE->value),
            $this->requireString($store, ArtifactField::NAME->value, ArtifactSection::STORE->value),
            $this->readNullableString($store, ArtifactField::DESCRIPTION->value),
            $sections
        );
    }

    /**
     * {@inheritdoc}
     */
    public function readKeyEntry(array $entry): ArtifactKeyEntry
    {
        $section = ArtifactSection::KEYS->value;
        $this->assertEntryShape($entry, ArtifactSection::KEYS);

        $definition = $entry[ArtifactField::DEFINITION->value] ?? null;
        $enabled = $entry[ArtifactField::ENABLED->value] ?? null;
        $localized = $entry[ArtifactField::LOCALIZED->value] ?? null;

        return new ArtifactKeyEntry(
            $this->requireString($entry, ArtifactField::IDENTIFIER->value, $section),
            $this->requireString($entry, ArtifactField::NAME->value, $section),
            $this->readNullableString($entry, ArtifactField::TITLE->value),
            $this->readNullableString($entry, ArtifactField::DESCRIPTION->value),
            $this->requireString($entry, ArtifactField::TYPE->value, $section),
            is_array($definition) ? $definition : null,
            // a key row whose column was never written reads as disabled, which is what it is
            is_bool($enabled) ? $enabled : false,
            // the one optional column whose own NULL is a state: a key that states no flag
            is_bool($localized) ? $localized : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function readAssignmentEntry(array $entry): ArtifactAssignmentEntry
    {
        $section = ArtifactSection::KEY_GROUP_RELATIONS->value;
        $this->assertEntryShape($entry, ArtifactSection::KEY_GROUP_RELATIONS);

        $mandatory = $entry[ArtifactField::MANDATORY->value] ?? null;

        return new ArtifactAssignmentEntry(
            $this->requireString($entry, ArtifactField::GROUP->value, $section),
            $this->requireString($entry, ArtifactField::KEY->value, $section),
            $this->readSorter($entry, ArtifactSection::KEY_GROUP_RELATIONS),
            is_bool($mandatory) ? $mandatory : false,
            $this->readNullableArray($entry, ArtifactField::METADATA->value, $section),
            $this->readNullableArray($entry, ArtifactField::DEFINITION_OVERRIDE->value, $section),
            $this->readNullableString($entry, ArtifactField::SUB_GROUP->value),
            array_key_exists(ArtifactField::METADATA->value, $entry),
            array_key_exists(ArtifactField::DEFINITION_OVERRIDE->value, $entry),
            array_key_exists(ArtifactField::SUB_GROUP->value, $entry)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function readSorter(array $entry, ArtifactSection $section): int
    {
        $sorter = $entry[ArtifactField::SORTER->value] ?? 0;

        if (!is_int($sorter)) {
            throw $this->invalidField($section->value, ArtifactField::SORTER->value, 'an integer');
        }

        return $sorter;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    private function readStoreEntry(array $document): array
    {
        $store = $document[ArtifactSection::STORE->value] ?? null;

        if (!is_array($store)) {
            throw new InvalidArgumentException('The configuration file carries no store entry.');
        }

        $this->assertEntryShape($store, ArtifactSection::STORE);

        return $store;
    }

    /**
     * Every section is keyed by what matches an entry to a row, so that apply can look an entry up
     * instead of scanning. A key used twice is refused rather than letting one entry drop out.
     *
     * @param array<string, mixed> $document
     *
     * @return array<string, array<string, mixed>>
     *
     * @throws InvalidArgumentException
     */
    private function readSection(array $document, ArtifactSection $section): array
    {
        $entries = $document[$section->value] ?? null;

        if (!is_array($entries)) {
            throw new InvalidArgumentException(
                sprintf('The configuration file carries no "%s" section.', $section->value)
            );
        }

        $keyed = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException(
                    sprintf('The "%s" section carries an entry that is not an object.', $section->value)
                );
            }

            $this->assertEntryShape($entry, $section);
            $key = $this->buildEntryKey($entry, $section);

            if (array_key_exists($key, $keyed)) {
                throw new InvalidArgumentException(
                    sprintf('The "%s" section names the same entry twice.', $section->value)
                );
            }

            $keyed[$key] = $entry;
        }

        return $keyed;
    }

    /**
     * Every field of the section's shape, refused where the file supplies one in another shape
     * rather than read as the default an absent field stands for.
     *
     * @param array<string, mixed> $entry
     *
     * @throws InvalidArgumentException
     */
    private function assertEntryShape(array $entry, ArtifactSection $section): void
    {
        foreach (self::ENTRY_SHAPE[$section->value] as $field => $shape) {
            $value = $entry[$field] ?? null;

            $valid = match ($shape) {
                self::REQUIRED_STRING => is_string($value) && $value !== '',
                self::OPTIONAL_STRING => $value === null || is_string($value),
                self::OPTIONAL_INT => $value === null || is_int($value),
                self::OPTIONAL_BOOL => $value === null || is_bool($value),
                default => $value === null || is_array($value),
            };

            if (!$valid) {
                throw $this->invalidField($section->value, $field, $shape);
            }
        }
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @throws InvalidArgumentException
     */
    private function buildEntryKey(array $entry, ArtifactSection $section): string
    {
        $endpoints = self::RELATION_ENDPOINTS[$section->value] ?? null;

        if ($endpoints === null) {
            return $this->requireString($entry, ArtifactField::IDENTIFIER->value, $section->value);
        }

        return $this->requireString($entry, $endpoints[0], $section->value)
            . self::ENTRY_KEY_SEPARATOR
            . $this->requireString($entry, $endpoints[1], $section->value);
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @throws InvalidArgumentException
     */
    private function requireString(array $entry, string $field, string $section): string
    {
        $value = $entry[$field] ?? null;

        if (!is_string($value) || $value === '') {
            throw $this->invalidField($section, $field, 'a non empty string');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function readNullableString(array $entry, string $field): ?string
    {
        $value = $entry[$field] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array<string, mixed>|null
     *
     * @throws InvalidArgumentException
     */
    private function readNullableArray(array $entry, string $field, string $section): ?array
    {
        $value = $entry[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw $this->invalidField($section, $field, 'an object');
        }

        return $value;
    }

    private function invalidField(string $section, string $field, string $expected): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf('The "%s" section needs %s in "%s" on every entry.', $section, $expected, $field)
        );
    }
}
