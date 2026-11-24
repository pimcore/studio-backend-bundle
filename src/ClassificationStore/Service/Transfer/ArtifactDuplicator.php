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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use function array_key_exists;
use function is_string;

/**
 * @internal
 */
final readonly class ArtifactDuplicator implements ArtifactDuplicatorInterface
{
    /**
     * The sections whose entries own an identifier, which is what is regenerated.
     */
    private const array ENTITY_SECTIONS = [
        ArtifactSection::SUB_GROUPS,
        ArtifactSection::COLLECTIONS,
        ArtifactSection::GROUPS,
        ArtifactSection::KEYS,
    ];

    /**
     * The fields each relation section names an entity by, and the section that entity lives in.
     */
    private const array RELATION_REFERENCES = [
        ArtifactSection::COLLECTION_GROUP_RELATIONS->value => [
            ArtifactField::COLLECTION->value => ArtifactSection::COLLECTIONS,
            ArtifactField::GROUP->value => ArtifactSection::GROUPS,
        ],
        ArtifactSection::KEY_GROUP_RELATIONS->value => [
            ArtifactField::GROUP->value => ArtifactSection::GROUPS,
            ArtifactField::KEY->value => ArtifactSection::KEYS,
            ArtifactField::SUB_GROUP->value => ArtifactSection::SUB_GROUPS,
        ],
    ];

    /**
     * The two fields each relation section is keyed by, in the order the parser joins them.
     */
    private const array RELATION_ENTRY_KEY = [
        ArtifactSection::COLLECTION_GROUP_RELATIONS->value => [
            ArtifactField::COLLECTION->value,
            ArtifactField::GROUP->value,
        ],
        ArtifactSection::KEY_GROUP_RELATIONS->value => [
            ArtifactField::GROUP->value,
            ArtifactField::KEY->value,
        ],
    ];

    public function __construct(
        private IdentifierGeneratorInterface $identifierGenerator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(ArtifactDocument $document): ArtifactDocument
    {
        $regenerated = $this->regenerateIdentifiers($document);
        $sections = [];

        foreach (self::ENTITY_SECTIONS as $section) {
            $sections[$section->value] = $this->rewriteEntities($document, $section, $regenerated);
        }

        foreach (self::RELATION_REFERENCES as $section => $references) {
            $sections[$section] = $this->rewriteRelations(
                $document->getEntries(ArtifactSection::from($section)),
                $references,
                self::RELATION_ENTRY_KEY[$section],
                $regenerated
            );
        }

        return new ArtifactDocument(
            $document->getStoreIdentifier(),
            $document->getStoreName(),
            $document->getStoreDescription(),
            $sections
        );
    }

    /**
     * One fresh identifier per entity the document carries, per section.
     *
     * @return array<string, array<string, string>>
     */
    private function regenerateIdentifiers(ArtifactDocument $document): array
    {
        $regenerated = [];

        foreach (self::ENTITY_SECTIONS as $section) {
            foreach ($document->getEntries($section) as $identifier => $entry) {
                $regenerated[$section->value][$identifier] = $this->identifierGenerator->generate();
            }
        }

        return $regenerated;
    }

    /**
     * @param array<string, array<string, string>> $regenerated
     *
     * @return array<string, array<string, mixed>>
     */
    private function rewriteEntities(
        ArtifactDocument $document,
        ArtifactSection $section,
        array $regenerated,
    ): array {
        $rewritten = [];

        foreach ($document->getEntries($section) as $identifier => $entry) {
            $fresh = $regenerated[$section->value][$identifier];
            $entry[ArtifactField::IDENTIFIER->value] = $fresh;
            $rewritten[$fresh] = $entry;
        }

        return $rewritten;
    }

    /**
     * @param array<string, array<string, mixed>> $entries
     * @param array<string, ArtifactSection> $references
     * @param array<int, string> $entryKeyFields
     * @param array<string, array<string, string>> $regenerated
     *
     * @return array<string, array<string, mixed>>
     */
    private function rewriteRelations(
        array $entries,
        array $references,
        array $entryKeyFields,
        array $regenerated,
    ): array {
        $rewritten = [];

        foreach ($entries as $entry) {
            foreach ($references as $field => $target) {
                // a field the entry leaves out stays left out: supplying it is what makes a write of it
                if (array_key_exists($field, $entry)) {
                    $entry[$field] = $this->rewriteReference($entry[$field], $target, $regenerated);
                }
            }

            $rewritten[$this->buildEntryKey($entry, $entryKeyFields)] = $entry;
        }

        return $rewritten;
    }

    /**
     * @param array<string, array<string, string>> $regenerated
     */
    private function rewriteReference(
        mixed $reference,
        ArtifactSection $target,
        array $regenerated,
    ): mixed {
        if (!is_string($reference)) {
            return $reference;
        }

        return $regenerated[$target->value][$reference] ?? $reference;
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<int, string> $fields
     */
    private function buildEntryKey(array $entry, array $fields): string
    {
        return (string) $entry[$fields[0]]
            . ArtifactParserInterface::ENTRY_KEY_SEPARATOR
            . (string) $entry[$fields[1]];
    }
}
