<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\RelationData;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
trait RelationDataTrait
{
    use ElementProviderTrait;

    /**
     * @param ElementInterface[] $relations
     * @return RelationData[]
     */
    private function getRelationElementsData(array $relations): array
    {
        $data = [];

        foreach ($relations as $relation) {
            $data[] = $this->getRelationElementData($relation);
        }

        return $data;
    }

    private function getAdvancedRelationElementData(ElementMetadata|ObjectMetadata $relation): array
    {

        return [
            'element' => $this->getRelationElementData($relation->getElement()),
            'fieldName' => $relation->getFieldname(),
            'columns' => $relation->getColumns(),
            'data' => $relation->getData(),
        ];
    }

    private function getRelationElementData(ElementInterface $relation): RelationData
    {
        return new RelationData(
            $relation->getId(),
            $this->getElementType($relation, true),
            $this->getSubType($relation),
            $relation->getRealFullPath(),
            $this->getPublished($relation)
        );
    }

    private function getSubType(ElementInterface $element): string
    {
        if ($element instanceof Concrete) {
            return $element->getClassName();
        }

        return $element->getType();
    }

    private function getPublished(ElementInterface $element): ?bool
    {
        if ($element instanceof Concrete || $element instanceof Document) {
            return $element->getPublished();
        }

        return null;
    }
}
