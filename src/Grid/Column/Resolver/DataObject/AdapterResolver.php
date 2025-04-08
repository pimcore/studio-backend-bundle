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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\FieldDefinitionTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class AdapterResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;
    use FieldDefinitionTrait;

    public function __construct(
        private readonly DataServiceInterface $dataService,
        private readonly InheritanceServiceInterface $inheritanceService,
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver

    ) {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function resolveForExport(Column $column, ElementInterface $element): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $classDefinition = $element->getClass();
        $fieldDefinition = $this->getFieldDefinition($column->getKey(), $classDefinition);
        $value = $this->dataService->getExportFieldValue($element, $fieldDefinition, $column->getKey());

        return $this->getColumnData($column, $value);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $classDefinition = $element->getClass();
        $fieldDefinition = $this->getFieldDefinition($column->getKey(), $classDefinition);

        $value = $this->dataService->getNormalizedValue(
            $this->getLocalizedValue($column, $element),
            $fieldDefinition
        );

        $inheritanceData = null;
        if ($classDefinition->getAllowInherit() && $fieldDefinition->supportsInheritance()) {
            $inheritanceData = $this->getInheritanceData($element, $fieldDefinition, $column->getKey());
        }

        return $this->getColumnData(
            $column,
            $value,
            $inheritanceData
        );
    }

    public function getType(): string
    {
        return 'dataobject.adapter';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }

    private function getInheritanceData(Concrete $element, Data $fieldDefinition, string $field): InheritanceData
    {
        return $this->dataObjectServiceResolver->useInheritedValues(
            false,
            function () use ($element, $fieldDefinition, $field) {
                return $this->inheritanceService->processFieldDefinition($element, $fieldDefinition, $field);
            }
        );
    }
}
