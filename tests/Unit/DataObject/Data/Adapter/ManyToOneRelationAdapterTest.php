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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
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
     * GHSA-pg5f-74vr-w237: the field can be pointed at any element id, so nothing but the id and type
     * the user supplied is returned for a target the user may not view.
     *
     * @throws Exception
     */
    public function testNormalizeWithholdsTargetDataWithoutViewAccess(): void
    {
        $document = $this->makeEmpty(Page::class, [
            'isPublished' => true,
            'getRealFullPath' => '/secret-docs/board',
            'getType' => 'page',
        ]);

        $data = $this->normalize($document, hasViewAccess: false);

        $this->assertSame('', $data['fullPath']);
        $this->assertSame('', $data['subtype']);
        $this->assertNull($data['isPublished']);
        $this->assertFalse($data['hasViewAccess']);
        $this->assertSame(['type' => 'document', 'id' => 7], [
            'type' => $data['type'],
            'id' => $data['id'],
        ]);
    }

    /**
     * @throws Exception
     */
    public function testNormalizeReturnsTargetDataWithViewAccess(): void
    {
        $document = $this->makeEmpty(Page::class, [
            'isPublished' => true,
            'getRealFullPath' => '/en/about',
            'getType' => 'page',
        ]);

        $data = $this->normalize($document);

        $this->assertSame('/en/about', $data['fullPath']);
        $this->assertSame('page', $data['subtype']);
        $this->assertTrue($data['isPublished']);
        $this->assertTrue($data['hasViewAccess']);
    }

    /**
     * @throws Exception
     */
    private function normalize(ElementInterface $element, bool $hasViewAccess = true): array
    {
        $adapter = new ManyToOneRelationAdapter(
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementDataServiceInterface::class, [
                'getRelatedElementData' => new RelatedElementData(
                    7,
                    'document',
                    'page',
                    '/en/about',
                    null,
                    $hasViewAccess
                ),
            ])
        );

        return $adapter->normalize(
            $element,
            $this->makeEmpty(ManyToOneRelation::class, [
                'normalize' => ['type' => 'document', 'id' => 7],
            ])
        );
    }
}
