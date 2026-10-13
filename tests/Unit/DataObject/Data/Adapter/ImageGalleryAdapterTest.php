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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\HotspotImageAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ImageGalleryAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ImageGalleryAdapterTest extends Unit
{
    /**
     * Regression test: without a value yet, the patch envelope itself was stored as gallery items.
     *
     * @throws Exception
     */
    public function testPatchOnGalleryWithoutValueMergesIntoEmptyGallery(): void
    {
        $patch = ['action' => 'add', 'data' => [['image' => ['id' => 5, 'type' => 'asset']]]];
        $patchService = $this->makeEmpty(PatchServiceInterface::class, [
            'handlePatchDataField' => Expected::once(function (array $fieldData, array $existingValues) use ($patch) {
                $this->assertSame($patch, $fieldData);
                $this->assertSame([], $existingValues);

                return [['image' => ['id' => 0, 'type' => 'asset']]];
            }),
        ]);

        $adapter = new ImageGalleryAdapter(
            new HotspotImageAdapter(
                $this->makeEmpty(DataServiceInterface::class),
                $this->makeEmpty(ServiceResolverInterface::class)
            ),
            $patchService
        );

        $result = $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(ImageGallery::class, ['getName' => 'gallery', 'normalize' => null]),
            'gallery',
            ['gallery' => $patch],
            $this->makeEmpty(UserInterface::class),
            isPatch: true
        );

        // The merged list is used: its one item has no image, so the hotspot adapter maps it to null.
        $this->assertSame([null], $result?->getItems());
    }
}
