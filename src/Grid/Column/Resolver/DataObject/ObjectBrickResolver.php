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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ObjectBrickKey;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use function count;

/**
 * @internal
 */
final class ObjectBrickResolver implements ColumnResolverInterface, CoreElementColumnResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;

    public function __construct(
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DataServiceInterface $dataService,
        private readonly InheritanceServiceInterface $inheritanceService,
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver

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
            $this->getLocalizedValueFromKey($objectBrickKey->getField(), $column->getLocale(), $element),
            $fieldDefinition
        );

        if ($value === []) {
            return $this->getColumnData(
                $column,
                null,
            );
        }

        $inheritanceData = null;
        if ($classDefinition->getAllowInherit() && $fieldDefinition->supportsInheritance()) {
            try {
                $inheritanceData = $this->getInheritanceData($element, $fieldDefinition, $objectBrickKey);
            } catch (NotFoundException) {
                // inheritance data not found (field not set in parent id)
            }
        }

        try {
            $value = $value[$objectBrickKey->getBrickName()][$objectBrickKey->getAttribute()];
        } catch (Exception) {
            $value = null;
        }

        return $this->getColumnData(
            $column,
            $value,
            $inheritanceData
        );
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
    private function getInheritanceData(Concrete $element, Data $fieldDefinition, ObjectBrickKey $key): InheritanceData
    {
        $inheritanceDataCollection = $this->dataObjectServiceResolver->useInheritedValues(
            false,
            function () use ($element, $fieldDefinition, $key) {
                return $this->inheritanceService->processFieldDefinition($element, $fieldDefinition, $key->getField());
            }
        );

        try {
            $inheritanceData = $inheritanceDataCollection[$key->getBrickName()][$key->getAttribute()];

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
