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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Patcher\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Adapter\IndexAdapter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class IndexAdapterTest extends Unit
{
    public function testSupportsDataObjectsAndDocuments(): void
    {
        $this->assertSame(
            [ElementTypes::TYPE_OBJECT, ElementTypes::TYPE_DOCUMENT],
            (new IndexAdapter())->supportedElementTypes()
        );
    }

    /**
     * @throws Exception
     */
    public function testSetsTheIndexOfADocument(): void
    {
        $document = $this->createMock(Page::class);
        $document->expects($this->once())->method('setIndex')->with(3);

        (new IndexAdapter())->patch($document, ['index' => 3], $this->makeEmpty(UserInterface::class));
    }

    /**
     * @throws Exception
     */
    public function testSetsTheIndexOfADataObject(): void
    {
        $object = $this->createMock(Concrete::class);
        $object->expects($this->once())->method('setIndex')->with(5);

        (new IndexAdapter())->patch($object, ['index' => 5], $this->makeEmpty(UserInterface::class));
    }

    /**
     * @throws Exception
     */
    public function testIgnoresAPatchWithoutIndex(): void
    {
        $document = $this->createMock(Page::class);
        $document->expects($this->never())->method('setIndex');

        (new IndexAdapter())->patch($document, ['parentId' => 1], $this->makeEmpty(UserInterface::class));
    }

    /**
     * @throws Exception
     */
    public function testIgnoresElementsWithoutAnIndex(): void
    {
        $asset = $this->createMock(Asset::class);

        (new IndexAdapter())->patch($asset, ['index' => 1], $this->makeEmpty(UserInterface::class));

        $this->addToAssertionCount(1);
    }

    /**
     * @throws Exception
     */
    public function testRejectsANonIntegerIndex(): void
    {
        $this->expectException(ElementSavingFailedException::class);

        (new IndexAdapter())->patch(
            $this->createMock(Page::class),
            ['index' => '3'],
            $this->makeEmpty(UserInterface::class)
        );
    }
}
