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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Bundle\StudioBackendBundle\Version\Model\DeletedRelationReference;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use function array_key_exists;
use function count;
use function in_array;
use function is_array;

/**
 * @internal
 */
final readonly class DeletedRelationService implements DeletedRelationServiceInterface
{
    private const string LOCALIZED_FIELDS = 'localizedfields';

    private const string SINGLE_RELATION = 'manyToOneRelation';

    private const array MULTIPLE_RELATIONS = ['manyToManyRelation', 'manyToManyObjectRelation'];

    /**
     * {@inheritdoc}
     */
    public function addDeletedRelations(array $objectData, ClassDefinition $class, array $references): array
    {
        usort(
            $references,
            static fn (DeletedRelationReference $a, DeletedRelationReference $b) => $a->getIndex() <=> $b->getIndex()
        );

        foreach ($references as $reference) {
            $objectData = $this->addDeletedRelation($objectData, $class, $reference);
        }

        return $objectData;
    }

    private function addDeletedRelation(
        array $objectData,
        ClassDefinition $class,
        DeletedRelationReference $reference
    ): array {
        $fieldName = $reference->getFieldName();
        $language = $reference->getLanguage();

        if ($language === null) {
            $fieldDefinition = $class->getFieldDefinition($fieldName);
            if (!$this->isSupported($fieldDefinition) || !array_key_exists($fieldName, $objectData)) {
                return $objectData;
            }

            $objectData[$fieldName] = $this->addEntry($objectData[$fieldName], $fieldDefinition, $reference);

            return $objectData;
        }

        $localizedFields = $class->getFieldDefinition(self::LOCALIZED_FIELDS);
        $fieldDefinition = $localizedFields instanceof Localizedfields
            ? $localizedFields->getFieldDefinition($fieldName)
            : null;
        $localizedValues = $objectData[self::LOCALIZED_FIELDS][$fieldName] ?? null;
        if (
            !$this->isSupported($fieldDefinition) ||
            !is_array($localizedValues) ||
            !array_key_exists($language, $localizedValues)
        ) {
            return $objectData;
        }

        $objectData[self::LOCALIZED_FIELDS][$fieldName][$language] = $this->addEntry(
            $localizedValues[$language],
            $fieldDefinition,
            $reference
        );

        return $objectData;
    }

    private function isSupported(?Data $fieldDefinition): bool
    {
        if ($fieldDefinition === null) {
            return false;
        }

        $fieldType = $fieldDefinition->getFieldType();

        return $fieldType === self::SINGLE_RELATION || in_array($fieldType, self::MULTIPLE_RELATIONS, true);
    }

    private function addEntry(mixed $value, Data $fieldDefinition, DeletedRelationReference $reference): mixed
    {
        $entry = $this->createEntry($reference);

        if ($fieldDefinition->getFieldType() === self::SINGLE_RELATION) {
            return $value ?? $entry;
        }

        $relations = is_array($value) ? $value : [];
        $index = min($reference->getIndex() ?? count($relations), count($relations));
        array_splice($relations, $index, 0, [$entry]);

        return $relations;
    }

    private function createEntry(DeletedRelationReference $reference): array
    {
        return [
            'id' => $reference->getElementId(),
            'type' => $reference->getElementType(),
            'subtype' => '',
            'fullPath' => '',
            'isPublished' => null,
            'hasViewAccess' => false,
            'isDeleted' => true,
        ];
    }
}
