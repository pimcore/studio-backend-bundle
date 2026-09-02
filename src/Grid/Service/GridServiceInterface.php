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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\AdvancedColumnPreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface GridServiceInterface
{
    public function getDocumentGridColumns(): ColumnCollection;

    public function getDataObjectGridColumns(ClassDefinition $classDefinition): ColumnCollection;

    /**
     * Whether the given (or, if omitted, the current) user is allowed to view the given locale
     * for the given element, based on the "Viewable Languages" workspace permission. Non-object
     * elements are always considered viewable.
     *
     * Callers reading a localized field MUST pass $isLocalizedField = true: a localized field
     * read without an explicit locale implicitly resolves to the current request locale or the
     * default language (Localizedfield::getLanguage()), so a null $locale is then authorized
     * against that effective locale instead of being skipped. Only for non-localized fields
     * ($isLocalizedField = false) does a null $locale mean no language is involved at all.
     */
    public function isLocaleViewableForElement(
        ElementInterface $element,
        ?string $locale,
        ?UserInterface $user = null,
        bool $isLocalizedField = false,
    ): bool;

    /**
     * @throws InvalidArgumentException|NotFoundException
     */
    public function getGridDataForElement(
        ColumnCollection $columnCollection,
        ?StudioElementInterface $element,
        string $elementType,
        int $elementId,
        bool $isExport = false,
        ?UserInterface $user = null,
    ): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getGridValuesForElement(
        ColumnCollection $columnCollection,
        string $elementType,
        int $elementId,
        UserInterface $user
    ): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getConfigurationForExport(
        array $config,
        array $columnsDefinitions
    ): ColumnCollection;

    /**
     * @throws InvalidArgumentException
     * @throws InvalidQueryTypeException
     * @throws InvalidElementTypeException
     */
    public function getAssetGrid(GridParameter $gridParameter): Collection;

    /**
     * @throws NotFoundException
     * @throws InvalidQueryTypeException
     * @throws InvalidElementTypeException
     * @throws Exception
     */
    public function getDataObjectGrid(
        GridParameter $gridParameter,
        ?string $classId
    ): Collection;

    /**
     * @throws InvalidArgumentException
     * @throws InvalidQueryTypeException
     * @throws InvalidElementTypeException
     */
    public function getDocumentGrid(GridParameter $gridParameter): Collection;

    /**
     * @throws Exception
     */
    public function getPreviewOfAdvancedColumn(
        AdvancedColumnPreviewParameter $parameter,
    ): ColumnData;

    public function getColumnKeys(ColumnCollection $columnCollection, bool $withGroup = false): array;

    /**
     * @return array<string, ColumnDefinitionInterface>
     */
    public function getColumnDefinitions(): array;

    /**
     * @return array<string, ColumnCollectorInterface>
     */
    public function getColumnCollectors(): array;

    /**
     * @return array<string, ColumnResolverInterface>
     */
    public function getColumnResolvers(): array;
}
