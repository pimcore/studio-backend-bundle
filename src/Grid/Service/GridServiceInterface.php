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

/**
 * @internal
 */
interface GridServiceInterface
{
    public function getDocumentGridColumns(): ColumnCollection;

    public function getDataObjectGridColumns(ClassDefinition $classDefinition): ColumnCollection;

    /**
     * @throws InvalidArgumentException
     */
    public function getGridDataForElement(
        ColumnCollection $columnCollection,
        StudioElementInterface $element,
        string $elementType,
        bool $isExport = false
    ): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getGridValuesForElement(
        ColumnCollection $columnCollection,
        StudioElementInterface $element,
        string $elementType,
        bool $isExport = false
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
     */
    public function getAssetGrid(GridParameter $gridParameter): Collection;

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function getDataObjectGrid(
        GridParameter $gridParameter,
        ?string $classId
    ): Collection;

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
