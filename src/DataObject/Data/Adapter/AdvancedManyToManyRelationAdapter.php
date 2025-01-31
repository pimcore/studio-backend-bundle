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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\RelationMetadataTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class AdvancedManyToManyRelationAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ElementProviderTrait;
    use RelationMetadataTrait;

    public function __construct(
        private RelationDataServiceInterface $relationDataService,
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    /**
     * @throws Exception
     */
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

        return $this->buildRelationsMetadata($relationData, $fieldDefinition);
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
            if (!$relation instanceof ElementMetadata) {
                continue;
            }

            $normalizedData[] = $this->relationDataService->getAdvancedRelationElementData($relation);
        }

        return $normalizedData;
    }

    /**
     * @throws Exception
     */
    private function buildRelationsMetadata(array $relationData, Data $fieldDefinition): array
    {
        if (!$fieldDefinition instanceof AdvancedManyToManyRelation) {
            return [];
        }

        $relationsMetadata = [];
        foreach ($relationData as $relation) {
            $elementData = $relation['element'];
            if (empty($elementData['id']) || empty($elementData['type'])) {
                continue;
            }

            $element = $this->getElement($this->serviceResolver, $elementData['type'], $elementData['id']);
            $fieldName = $fieldDefinition->getName();
            $relationsMetadata[] = $this->addRelationMetadata(
                $element,
                $relation['data'],
                new ElementMetadata($fieldName, $fieldDefinition->getColumnKeys(), $element)
            );
        }

        return $relationsMetadata;
    }
}
