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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ObjectBrickKey;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function count;

/**
 * @internal
 */
final class ObjectBrickResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function __construct(
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DataServiceInterface $dataService,
        private readonly InheritanceServiceInterface $inheritanceService,
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private readonly ObjectBrickServiceInterface $objectBrickService,
        private readonly DefinitionResolverInterface $definitionResolver

    ) {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $objectBrickKey = $this->mapObjectBrickKey($column->getKey());

        $classDefinition = $this->classDefinitionResolver->getById($element->getClassId());
        $fieldDefinition = $this->getFieldDefinition($objectBrickKey->getField(), $classDefinition);

        $value = $this->dataService->getNormalizedValue(
            $this->getLocalizedValueFromKey($objectBrickKey->getField(), null, $element),
            $fieldDefinition
        );

        $objectBrickFieldType = $this->objectBrickService->findObjectBrickField(
            $objectBrickKey->getBrickName(),
            $objectBrickKey->getAttribute(),
        )->getFieldType();

        if ($value === []) {
            return $this->getColumnData(
                $column,
                null,
                $objectBrickFieldType
            );
        }

        $inheritanceData = null;
        if ($classDefinition->getAllowInherit() && $fieldDefinition->supportsInheritance()) {
            try {
                $inheritanceData = $this->getInheritanceData($element, $fieldDefinition, $objectBrickKey, $column);
            } catch (NotFoundException) {
                // inheritance data not found (field not set in parent id)
            }
        }

        try {
            $returnValue = null;
            if ($column->getLocale()) {
                $returnValue = $value[$objectBrickKey->getBrickName()]['localizedfields'][$objectBrickKey->getAttribute()][$column->getLocale()];
            }

            if (!$column->getLocale()) {
                $returnValue = $value[$objectBrickKey->getBrickName()][$objectBrickKey->getAttribute()];
            }
        } catch (Exception) {
            $value = null;
        }

        return $this->getColumnData(
            $column,
            $returnValue,
            $objectBrickFieldType,
            $inheritanceData
        );
    }

    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        try {
            $objectBrickKey = $this->mapObjectBrickKey($column->getKey());

            $brickClass = $this->definitionResolver->getByKey($objectBrickKey->getBrickName());
            $fieldDefinition = $brickClass->getFieldDefinition($objectBrickKey->getAttribute());

            $brickContainer = $element->get($objectBrickKey->getField());
            if (!$brickContainer) {
                return $this->getColumnData($column, null, $fieldDefinition->getFieldType());
            }

            $brick = $brickContainer->get($objectBrickKey->getBrickName());

            if ($column->getLocale()) {
                $brick = $brick->get('localizedfields');
            }

            if (!$brick) {
                return $this->getColumnData($column, null, $fieldDefinition->getFieldType());
            }

            $context = new FieldContextData(
                contextObject: $brick,
                legacyParameters: ['context' => [
                    'containerType' => 'objectbrick',
                    'containerKey' => $objectBrickKey->getBrickName(),
                    'fieldname' => $objectBrickKey->getAttribute(),
                ],
                'language' => $column->getLocale(),
            ]
            );

            $value = $this->dataService->getExportFieldValue(
                $element,
                $fieldDefinition,
                $objectBrickKey->getField(),
                $context
            );

            return $this->getColumnData($column, $value, $fieldDefinition->getFieldType());

        } catch (Exception) {
            return $this->getColumnData($column, null, $column->getType());
        }
    }

    public function getType(): string
    {
        return 'dataobject.objectbrick';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    private function getFieldDefinition(string $field, ClassDefinition $classDefinition): Data
    {
        $fieldDefinition = $classDefinition->getFieldDefinition($field);

        if (!$fieldDefinition instanceof Data) {
            throw new NotFoundException('Field definition', $field);
        }

        return $fieldDefinition;
    }

    /**
     * @throws NotFoundException
     */
    private function getInheritanceData(Concrete $element, Data $fieldDefinition, ObjectBrickKey $key, Column $column): InheritanceData
    {
        $inheritanceDataCollection = $this->dataObjectServiceResolver->useInheritedValues(
            false,
            function () use ($element, $fieldDefinition, $key) {
                return $this->inheritanceService->processFieldDefinition($element, $fieldDefinition, $key->getField());
            }
        );

        try {
            $inheritanceData = null;
            if ($column->getLocale()) {
                $inheritanceData = $inheritanceDataCollection[$key->getBrickName()]['localizedfields'][$key->getAttribute()][$column->getLocale()];
            }

            if (!$column->getLocale()) {
                $inheritanceData = $inheritanceDataCollection[$key->getBrickName()][$key->getAttribute()];
            }

            if (!$inheritanceData instanceof InheritanceData) {
                throw new Exception();
            }

        } catch (Exception) {
            throw new NotFoundException('Inheritance data', $key->getBrickName() . '.' . $key->getAttribute());
        }

        return $inheritanceData;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function mapObjectBrickKey(string $key): ObjectBrickKey
    {
        $split = explode('.', $key);
        if (count($split) !== 3) {
            throw new InvalidArgumentException(
                'invalid key structure for object brick.
                          Is has to be in the format of <field>.<brickname>.<attributeofbrick>'
            );
        }

        return new ObjectBrickKey(
            field: $split[0],
            brickName: $split[1],
            attribute: $split[2]
        );
    }
}
