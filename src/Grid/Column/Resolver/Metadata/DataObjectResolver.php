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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\Metadata;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata\CoreLocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\Metadata\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class DataObjectResolver implements
    ColumnResolverInterface,
    StudioElementColumnResolverInterface,
    ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;
    use CoreLocalizedValueTrait;

    public function __construct(
        private readonly DataObjectServiceInterface $dataObjectService
    ) {
    }

    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        $dataObject = $this->getCoreLocalizedValue($column, $element);

        if (!$dataObject instanceof DataObject) {
            return $this->getColumnData($column, null, $this->getType());
        }

        return $this->getColumnData($column, $dataObject->getFullPath(), $this->getType());
    }

    public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
    {
        $object = $this->getLocalizedValue($column, $element);

        if (!isset($object['object'])) {
            return $this->getColumnData($column, null, $this->getType());
        }

        try {
            $relatedObject = $this->dataObjectService->getDataObject(
                reset($object['object']),
                false
            );
        } catch (NotFoundException) {
            return $this->getColumnData($column, null, $this->getType());
        }

        return $this->getColumnData(
            $column,
            [
                'id' => $relatedObject->getId(),
                'subtype' => $relatedObject->getType(),
                'type' => 'object',
                'fullPath' => $relatedObject->getFullPath(),
                'isPublished' => $relatedObject->isPublished(),
            ],
            $this->getType()
        );
    }

    public function getType(): string
    {
        return ColumnType::METADATA_DATA_OBJECT->value;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
