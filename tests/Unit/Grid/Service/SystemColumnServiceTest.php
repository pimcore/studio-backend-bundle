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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SystemColumnService;

/**
 * @internal
 */
#[CoversClass(SystemColumnService::class)]
#[UsesClass(ColumnMapper::class)]
final class SystemColumnServiceTest extends TestCase
{
    public function testGetSystemColumnsForAssets(): void
    {
        $mapper = new ColumnMapper();
        $systemColumnService = new SystemColumnService($mapper);

        $this->assertSame([
            'preview' => 'preview',
            'id' => 'id',
            'type' => 'string',
            'fullpath' => 'string',
            'filename' => 'string',
            'creationDate' => 'datetime',
            'modificationDate' => 'datetime',
            'fileSize' => 'fileSize',
            'mimetype' => 'string',
        ], $systemColumnService->getSystemColumnsForAssets());
    }

    public function testGetSystemColumnsForDataObjects(): void
    {
        $mapper = new ColumnMapper();
        $systemColumnService = new SystemColumnService($mapper);

        $this->assertSame([
            'id' => 'id',
            'fullpath' => 'string',
            'key' => 'string',
            'published' => 'boolean',
            'creationDate' => 'datetime',
            'modificationDate' => 'datetime',
            'filename' => 'string',
            'classname' => 'string',
            'index' => 'integer',
            'type' => 'string',
        ], $systemColumnService->getSystemColumnsForDataObjects());
    }
}
