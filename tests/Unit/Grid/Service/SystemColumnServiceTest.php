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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SystemColumnService;

/**
 * @internal
 */
final class SystemColumnServiceTest extends Unit
{
    public function testGetSystemColumnsForAssets(): void
    {
        $mapper = new ColumnMapper();
        $systemColumnService = new SystemColumnService($mapper);

        $this->assertSame([
            'preview' => 'image',
            'id' => 'integer',
            'type' => 'string',
            'fullpath' => 'string',
            'filename' => 'string',
            'creationDate' => 'datetime',
            'modificationDate' => 'datetime',
            'size' => 'fileSize',
        ], $systemColumnService->getSystemColumnsForAssets());
    }

    public function testGetSystemColumnsForDataObjects(): void
    {
        $mapper = new ColumnMapper();
        $systemColumnService = new SystemColumnService($mapper);

        $this->assertSame([
            'id' => 'integer',
            'fullpath' => 'string',
            'key' => 'string',
            'published' => 'boolean',
            'creationDate' => 'datetime',
            'modificationDate' => 'datetime',
            'filename' => 'string',
            'classname' => 'string',
            'index' => 'integer',
        ], $systemColumnService->getSystemColumnsForDataObjects());
    }
}