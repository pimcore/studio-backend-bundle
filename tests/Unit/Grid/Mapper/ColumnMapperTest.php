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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Mapper;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper;

/**
 * @internal
 */
final class ColumnMapperTest extends TestCase
{
    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperWithUnsupportedColumn(): void
    {
        $mapper = new ColumnMapper();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "unsupported" not supported.');

        $mapper->getType('unsupported');
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForPreview(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('preview', $mapper->getType('preview'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForId(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('id', $mapper->getType('id'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForType(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('type'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForFullPath(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('fullpath'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForFileName(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('filename'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForCreationDate(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('datetime', $mapper->getType('creationDate'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForModificationDate(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('datetime', $mapper->getType('modificationDate'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForSize(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('fileSize', $mapper->getType('fileSize'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForKey(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('key'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForPublished(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('boolean', $mapper->getType('published'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForClassName(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('string', $mapper->getType('classname'));
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\ColumnMapper::getType
     */
    public function testMapperForIndex(): void
    {
        $mapper = new ColumnMapper();
        $this->assertSame('integer', $mapper->getType('index'));
    }
}
