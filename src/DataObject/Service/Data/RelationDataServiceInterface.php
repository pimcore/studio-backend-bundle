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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
interface RelationDataServiceInterface
{
    /**
     * @param ElementInterface[] $relations
     *
     * @return RelatedElementData[]
     */
    public function getRelationElementsData(array $relations): array;

    /**
     * Returns the related elements of a relation field that opted in to optimized admin loading without loading
     * them, or null when the regular loading has to be used (see the implementation for the conditions).
     *
     * @return RelatedElementData[]|null
     */
    public function getLightweightRelationData(Concrete $element, Data $fieldDefinition, string $key): ?array;

    /**
     * Resolves the inheritance of a relation field that opted in to optimized admin loading from the relation
     * rows of the object and its ancestors, or null when the regular resolution has to be used.
     */
    public function getLightweightInheritanceData(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): ?InheritanceData;

    public function getAdvancedRelationElementData(ElementMetadata|ObjectMetadata $relation): array;

    /**
     * Builds the rows of an advanced relation from the target ids, with one query per element type
     * instead of loading every target. Rows whose target no longer exists are skipped.
     *
     * @param array<int, ElementMetadata|ObjectMetadata> $relations
     *
     * @return array<int, array{element: RelatedElementData, fieldName: ?string, columns: array, data: array}>
     *
     * @throws DatabaseException
     */
    public function getAdvancedRelationElementsData(array $relations): array;

    public function getSetterData(
        Concrete $element,
        Data $fieldDefinition,
        DataNormalizerInterface $adapter,
        bool $isPatch,
        ?array $fieldData,
        ?FieldContextData $contextData,
        bool $isAdvanced = false
    ): ?array;
}
