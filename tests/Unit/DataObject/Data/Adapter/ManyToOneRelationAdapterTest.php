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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ManyToOneRelationAdapter;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class ManyToOneRelationAdapterTest extends Unit
{
    /**
     * Regression test (PEES-2045): an unpublished document target was reported without a published
     * state, so the UI could not mark it as unpublished.
     *
     * @throws Exception
     */
    public function testNormalizeReportsUnpublishedDocument(): void
    {
        $document = $this->makeEmpty(Page::class, [
            'isPublished' => false,
            'getRealFullPath' => '/en/about',
            'getType' => 'page',
        ]);

        $this->assertFalse($this->normalize($document)['isPublished']);
    }

    /**
     * @throws Exception
     */
    public function testNormalizeReportsPublishedDocument(): void
    {
        $document = $this->makeEmpty(Page::class, [
            'isPublished' => true,
            'getRealFullPath' => '/en/about',
            'getType' => 'page',
        ]);

        $this->assertTrue($this->normalize($document)['isPublished']);
    }

    /**
     * @throws Exception
     */
    public function testNormalizeReportsUnpublishedDataObject(): void
    {
        $object = $this->makeEmpty(Concrete::class, [
            'isPublished' => false,
            'getRealFullPath' => '/Product Data/Cars/jaguar/MK2',
            'getType' => 'object',
        ]);

        $this->assertFalse($this->normalize($object)['isPublished']);
    }

    /**
     * Assets have no published state, so none is reported.
     *
     * @throws Exception
     */
    public function testNormalizeReportsNoPublishedStateForAsset(): void
    {
        $asset = $this->makeEmpty(Image::class, [
            'getRealFullPath' => '/Car Images/cobra.jpg',
            'getType' => 'image',
        ]);

        $this->assertNull($this->normalize($asset)['isPublished']);
    }

    /**
     * @throws Exception
     */
    private function normalize(ElementInterface $element): array
    {
        $adapter = new ManyToOneRelationAdapter($this->makeEmpty(ServiceResolverInterface::class));

        return $adapter->normalize(
            $element,
            $this->makeEmpty(ManyToOneRelation::class, [
                'normalize' => ['type' => 'document', 'id' => 7],
            ])
        );
    }
}
