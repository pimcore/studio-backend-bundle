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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class AdvancedManyToManyRelationAdapter implements SetterDataInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver
    )
    {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?array
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $relationData = $data[$key];
        if ($relationData === false || !is_array($relationData)) {
            return null;
        }

        return $this->buildRelationsMetadata($relationData, $fieldDefinition);
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
            $element = $this->getElement($this->serviceResolver, $relation['type'], $relation['id']);
            $relationsMetadata[] = $this->createObjectMetadata($element, $fieldDefinition, $relation);
        }

        return $relationsMetadata;
    }

    /**
     * @throws Exception
     */
    private function createObjectMetadata(
        ElementInterface $element,
        AdvancedManyToManyRelation $fieldDefinition,
        array $relation,
    ): ElementMetadata
    {
        $metaData = new ElementMetadata(
            $fieldDefinition->getName(),
            $fieldDefinition->getColumnKeys(),
            $element
        );
        $metaData->_setOwner($element);
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
