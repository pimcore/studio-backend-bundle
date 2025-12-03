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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectDetail;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Model\DataObject as DataObjectModel;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionAbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstractData;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version as DataObjectVersionModal;

/**
 * @internal
 */
interface DataServiceInterface
{
    /**
     * @throws DatabaseException|NotFoundException
     */
    public function setObjectDetailData(
        DataObjectFolder|DataObjectDetail|DataObjectVersion $dataObject,
        DataObjectModel $element,
        ?DataObjectVersionModal $version = null,
    ): void;

    public function getNormalizedValue(
        mixed $value,
        Data $fieldDefinition
    ): mixed;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function getPreviewObjectData(Concrete $dataObject): array;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): mixed;

    public function getPreviewFieldName(Data $fieldDefinition): string;

    /**
     * @throws ElementSavingFailedException
     */
    public function updateEditableData(Concrete $element, array $editableData, UserInterface $user): void;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function handleDraftData(Concrete $draftElement, Concrete $element, ?string $task = null): void;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function getExportObjectData(Concrete $dataObject, ColumnCollection $columnCollection): array;

    public function getExportFieldValue(
        Concrete|Localizedfield|ObjectbrickAbstractData|FieldcollectionAbstractData $dataObject,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string;
}
