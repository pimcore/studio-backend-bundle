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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\MappedParameter;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportFolderFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\Element\ElementDescriptor;

/**
 * @internal
 */
final class ExportFolderFileParameterTest extends Unit
{
    public function testThrowsInvalidArgumentExceptionWhenFoldersEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExportFolderFileParameter([], null);
    }

    public function testGetFiltersReturnsDefaultFilterParameterWhenNull(): void
    {
        $parameter = new ExportFolderFileParameter([1], null);
        $filters = $parameter->getFilters();

        $this->assertInstanceOf(FilterParameter::class, $filters);
    }

    public function testGetFoldersReturnsElementDescriptors(): void
    {
        $parameter = new ExportFolderFileParameter([1, 2, 3], null);
        $folders = $parameter->getFolders();

        $this->assertCount(3, $folders);
        foreach ($folders as $folder) {
            $this->assertInstanceOf(ElementDescriptor::class, $folder);
        }
    }
}
