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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementInterface as IndexElementInterface;
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
        IndexElementInterface $element,
        string $elementType
    ): array;

    /**
     * @throws InvalidArgumentException
     */
    public function getGridValuesForElement(
        ColumnCollection $columnCollection,
        IndexElementInterface $element,
        string $elementType
    ): array;

    public function getConfigurationFromArray(array $config, bool $isExport = false): ColumnCollection;

    public function getAssetGrid(GridParameter $gridParameter): Collection;

    public function getDataObjectGrid(GridParameter $gridParameter): Collection;

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
