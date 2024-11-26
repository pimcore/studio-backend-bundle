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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\RelationData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ManyToManyRelationAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?array {
        $relationData = $data[$key];
        if ($relationData === false || !is_array($relationData)) {
            return null;
        }

        return $this->getRelationElements($relationData);
    }

    /**
     * @return RelationData[]|null
     */
    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $data = [];
        /** @var ElementInterface[] $value */
        foreach ($value as $relation) {
            $elementType = $this->getElementType($relation);
            $data[] = new RelationData(
                $relation->getId(),
                $elementType,
                $this->getSubType($relation),
                $relation->getFullPath(),
                $this->getPublished($relation)
            );
        }

        return $data;
    }

    private function getRelationElements(array $relationData): array
    {
        $relations = [];
        foreach ($relationData as $relation) {
            try {
                $element = $this->getElement($this->serviceResolver, $relation['type'], $relation['id']);
            } catch (NotFoundException) {
                continue;
            }

            $relations[] = $element;
        }

        return $relations;
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
