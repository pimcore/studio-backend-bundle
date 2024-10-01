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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class AdvancedManyToManyObjectRelationAdapter implements SetterDataInterface
{
    public function __construct(
        private ConcreteObjectResolverInterface $concreteObjectResolver
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?array {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $relationData = $data[$key];
        if ($relationData === false || !is_array($relationData)) {
            return null;
        }

        return $this->buildRelationsMetadata($relationData, $fieldDefinition);
    }

    private function buildRelationsMetadata(array $relationData, Data $fieldDefinition): array
    {
        if (!$fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            return [];
        }

        $relationsMetadata = [];
        foreach ($relationData as $relation) {
            $object = $this->concreteObjectResolver->getById($relation['id']);
            if ($object && $object->getClassName() === $fieldDefinition->getAllowedClassId()) {
                $relationsMetadata[] = $this->createObjectMetadata($object, $fieldDefinition, $relation);
            }
        }

        return $relationsMetadata;
    }

    private function createObjectMetadata(
        Concrete $object,
        AdvancedManyToManyObjectRelation $fieldDefinition,
        array $relation,
    ): ObjectMetadata {
        $metaData = new ObjectMetadata(
            $fieldDefinition->getName(),
            $fieldDefinition->getColumnKeys(),
            $object
        );
        $metaData->_setOwner($object);
        $metaData->_setOwnerFieldname($fieldDefinition->getName());

        foreach ($fieldDefinition->getColumns() as $column) {
            $setter = 'set' . ucfirst($column['key']);
            $value = $relation[$column['key']] ?? null;

            if ($column['type'] === 'multiselect' && is_array($value)) {
                $value = implode(',', $value);
            }

            $metaData->$setter($value);
        }

        return $metaData;
    }
}
