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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassIdParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassNameParametersInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter as SortFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use function count;
use function explode;
use function in_array;

/**
 * @internal
 */
final class SortFilter implements FilterInterface
{
    use MapsSystemColumnFieldTrait;

    // The index stores a quantity value as {value, unitId}, it is sorted by the number.
    private const string QUANTITY_VALUE_SORT_SUFFIX = '.value';

    public function __construct(
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DefinitionResolverInterface $objectBrickDefinitionResolver
    ) {
    }

    /**
     * @throws Exception
     */
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof SortFilterParameterInterface) {
            return $query;
        }

        $classDefinition = $this->getClassDefinition($parameters, $query);

        foreach ($parameters->getSortFilters() as $sortFilter) {
            $sortDirection = SortDirection::ASC;

            if (strtolower($sortFilter->getDirection()) === SortDirection::DESC->value) {
                $sortDirection = SortDirection::DESC;
            }

            $query->orderByField(
                $this->getIndexField($sortFilter, $classDefinition),
                $sortDirection,
            );
        }

        return $query;
    }

    /**
     * @throws Exception
     */
    private function getClassDefinition(
        SortFilterParameterInterface $parameters,
        QueryInterface $query
    ): ?ClassDefinition {
        if (!$query instanceof DataObjectQueryInterface) {
            return null;
        }

        if ($parameters instanceof ClassNameParametersInterface) {
            $className = $parameters->getClassName();
            if ($className !== null && $className !== '') {
                return $this->classDefinitionResolver->getByName($className);
            }
        }

        // The folder export and batch edit pass only the class id.
        if ($parameters instanceof ClassIdParametersInterface) {
            $classId = $parameters->getClassId();
            if ($classId !== null && $classId !== '') {
                return $this->classDefinitionResolver->getById($classId);
            }
        }

        return null;
    }

    private function getIndexField(SortFilterParameter $sortFilter, ?ClassDefinition $classDefinition): string
    {
        $indexField = $this->mapColumnKeyToIndexField($sortFilter->getKey());
        if ($classDefinition === null) {
            return $indexField;
        }

        $fieldDefinition = $this->getFieldDefinition($classDefinition, $sortFilter->getKeyWithOutLocale());
        if ($fieldDefinition instanceof QuantityValue) {
            return $indexField . self::QUANTITY_VALUE_SORT_SUFFIX;
        }

        return $indexField;
    }

    /**
     * Grid columns name a class field <field> and an object brick field <field>.<brick>.<attribute>.
     */
    private function getFieldDefinition(ClassDefinition $classDefinition, string $key): ?Data
    {
        $keyParts = explode('.', $key);
        if (count($keyParts) === 1) {
            return $this->getContainerFieldDefinition($classDefinition, $key);
        }

        if (count($keyParts) !== 3) {
            return null;
        }

        [$field, $brickKey, $attribute] = $keyParts;
        $bricksField = $classDefinition->getFieldDefinition($field);
        if (!$bricksField instanceof Objectbricks || !in_array($brickKey, $bricksField->getAllowedTypes(), true)) {
            return null;
        }

        $objectBrick = $this->objectBrickDefinitionResolver->getByKey($brickKey);
        if ($objectBrick === null) {
            return null;
        }

        return $this->getContainerFieldDefinition($objectBrick, $attribute);
    }

    private function getContainerFieldDefinition(
        ClassDefinition|ObjectBrickDefinition $container,
        string $name
    ): ?Data {
        $fieldDefinition = $container->getFieldDefinition($name);
        if ($fieldDefinition !== null) {
            return $fieldDefinition;
        }

        $localizedFields = $container->getFieldDefinition('localizedfields');
        if (!$localizedFields instanceof Localizedfields) {
            return null;
        }

        return $localizedFields->getFieldDefinition($name);
    }
}
