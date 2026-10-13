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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
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

    public function getAdvancedRelationElementData(ElementMetadata|ObjectMetadata $relation): array;

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
