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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Mapper;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper;

/**
 * @internal
 */
final class ColumnMapperTest extends Unit
{
    public function testMapperWithUnsupportedColumn(): void
    {
        $mapper = new ColumnMapper();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "unsupported" not supported.');

        $mapper->getType('unsupported');
    }

    public function testMapperForPreview(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('image', $mapper->getType('preview'));
    }

    public function testMapperForId(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('integer', $mapper->getType('id'));
    }

    public function testMapperForType(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('type'));
    }

    public function testMapperForFullPath(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('fullpath'));
    }

    public function testMapperForFileName(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('filename'));
    }

    public function testMapperForCreationDate(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('datetime', $mapper->getType('creationDate'));
    }

    public function testMapperForModificationDate(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('datetime', $mapper->getType('modificationDate'));
    }

    public function testMapperForSize(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('fileSize', $mapper->getType('size'));
    }

    public function testMapperForKey(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('key'));
    }

    public function testMapperForPublished(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('boolean', $mapper->getType('published'));
    }

    public function testMapperForClassName(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('classname'));
    }

    public function testMapperForIndex(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('integer', $mapper->getType('index'));
    }
}
