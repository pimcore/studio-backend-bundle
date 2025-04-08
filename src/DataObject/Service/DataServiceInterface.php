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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Model\DataObject as DataObjectModel;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
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
        DataObjectFolder|DataObject|DataObjectVersion $dataObject,
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
        Concrete $dataObject,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string;
}
