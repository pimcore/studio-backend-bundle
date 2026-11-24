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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery as ImageGalleryData;
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

    /**
     * @throws Exception
     */
    public function testAStoreKeyExportsTheSameStringAsAPlainField(): void
    {
        $gallery = $this->createGallery('/a.jpg', '/b.jpg');
        $store = $this->makeEmpty(Classificationstore::class, [
            'getLocalizedKeyValue' => static fn (int $group, int $key, string $language) => $group === 3
                && $key === 7 && $language === 'en' ? $gallery : null,
        ]);

        $plain = $this->export(
            $this->makeEmpty(Concrete::class, ['get' => $gallery]),
            new FieldContextData(legacyParameters: ['language' => null])
        );
        $fromStore = $this->export(
            $this->makeEmpty(Concrete::class, ['get' => $store]),
            new FieldContextData(legacyParameters: ['context' => $this->address()])
        );

        $this->assertSame('/a.jpg, /b.jpg', $plain);
        $this->assertSame($plain, $fromStore);
    }

    /**
     * @throws Exception
     */
    public function testAnInjectedValueWinsOverTheStoreAddress(): void
    {
        $store = $this->makeEmpty(Classificationstore::class, [
            'getLocalizedKeyValue' => $this->createGallery('/default.jpg'),
        ]);

        $this->assertSame(
            '/variant.jpg',
            $this->export(
                $this->makeEmpty(Concrete::class, ['get' => $store]),
                new FieldContextData(legacyParameters: [
                    'context' => $this->address(),
                    'injectedData' => $this->createGallery('/variant.jpg'),
                ])
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testWithoutAnAddressThePlainFieldIsRead(): void
    {
        $object = $this->makeEmpty(Concrete::class, ['get' => $this->createGallery('/a.jpg', '')]);

        $this->assertSame('/a.jpg', $this->export($object, null));
        $this->assertSame('/a.jpg', $this->export($object, new FieldContextData(legacyParameters: ['x' => 1])));
        $this->assertSame('', $this->export($this->makeEmpty(Concrete::class, ['get' => null]), null));
    }

    /**
     * @throws Exception
     */
    public function testAnItemWithoutAnImageObjectIsSkipped(): void
    {
        $gallery = new ImageGalleryData([null, new Hotspotimage($this->makeEmpty(Image::class, ['getFrontendFullPath' => '/a.jpg']))]);

        $this->assertSame('/a.jpg', $this->export($this->makeEmpty(Concrete::class, ['get' => $gallery]), null));
    }

    /**
     * @throws Exception
     */
    private function export(Concrete $object, ?FieldContextData $contextData): string
    {
        $adapter = new ImageGalleryAdapter(
            new HotspotImageAdapter(
                $this->makeEmpty(DataServiceInterface::class),
                $this->makeEmpty(ServiceResolverInterface::class)
            ),
            $this->makeEmpty(PatchServiceInterface::class)
        );

        return $adapter->getExportData($object, new ImageGallery(), 'gallery', $contextData);
    }

    private function address(): array
    {
        return [
            'containerType' => 'classificationstore',
            'fieldname' => 'store',
            'groupId' => 3,
            'keyId' => 7,
            'language' => 'en',
        ];
    }

    /**
     * @throws Exception
     */
    private function createGallery(string ...$paths): ImageGalleryData
    {
        $items = [];
        foreach ($paths as $path) {
            $items[] = new Hotspotimage(
                $path === '' ? null : $this->makeEmpty(Image::class, ['getFrontendFullPath' => $path])
            );
        }

        return new ImageGalleryData($items);
    }
}
