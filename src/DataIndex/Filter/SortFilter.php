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
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ObjectBrickKey;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter as SortFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilterParameterInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use function in_array;
use function str_contains;

/**
 * @internal
 */
final class SortFilter implements FilterInterface
{
    use MapsSystemColumnFieldTrait;

    // The index stores a quantity value as {value, unitId}, it is sorted by the number.
    private const string VALUE_FIELD_SUFFIX = '.value';

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
            return $indexField . self::VALUE_FIELD_SUFFIX;
        }

        return $indexField;
    }

    private function getFieldDefinition(ClassDefinition $classDefinition, string $key): ?Data
    {
        if (!str_contains($key, '.')) {
            return $classDefinition->getFieldDefinition($key);
        }

        $objectBrickKey = ObjectBrickKey::fromColumnKey($key);
        if ($objectBrickKey === null) {
            return null;
        }

        $brickName = $objectBrickKey->getBrickName();
        $bricksField = $classDefinition->getFieldDefinition($objectBrickKey->getField());
        if (!$bricksField instanceof Objectbricks || !in_array($brickName, $bricksField->getAllowedTypes(), true)) {
            return null;
        }

        return $this->objectBrickDefinitionResolver
            ->getByKey($brickName)
            ?->getFieldDefinition($objectBrickKey->getAttribute());
    }
}
