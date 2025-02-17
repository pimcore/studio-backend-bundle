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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\RelationMetadataTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class AdvancedManyToManyObjectRelationAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use RelationMetadataTrait;

    public function __construct(
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private RelationDataServiceInterface $relationDataService,
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?array {
        $relationData = $this->relationDataService->getSetterData(
            $element,
            $fieldDefinition,
            $this,
            $isPatch,
            $data[$key],
            $contextData,
            true
        );
        if ($relationData === null) {
            return null;
        }

        return $this->buildObjectRelationsMetadata($relationData, $fieldDefinition);
    }

    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!is_array($value)) {
            return null;
        }

        $normalizedData = [];
        foreach ($value as $relation) {
            if (!$relation instanceof ObjectMetadata) {
                continue;
            }

            $normalizedData[] = $this->relationDataService->getAdvancedRelationElementData($relation);
        }

        return $normalizedData;
    }

    private function buildObjectRelationsMetadata(array $relationData, Data $fieldDefinition): array
    {
        if (!$fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            return [];
        }

        $relationsMetadata = [];
        foreach ($relationData as $relation) {
            if (empty($relation['element']['id']) || $relation['element']['type'] !== ElementTypes::TYPE_OBJECT) {
                continue;
            }

            $object = $this->concreteObjectResolver->getById($relation['element']['id']);
            if ($object && $object->getClassName() === $fieldDefinition->getAllowedClassId()) {
                $fieldName = $fieldDefinition->getName();
                $relationsMetadata[] = $this->addRelationMetadata(
                    $object,
                    $relation['data'],
                    new ObjectMetadata($fieldName, $fieldDefinition->getColumnKeys(), $object)
                );
            }
        }

        return $relationsMetadata;
    }
}
