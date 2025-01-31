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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\SubTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ImageGallery as ImageGalleryData;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ImageGalleryAdapter implements SetterDataInterface
{
    use ValidateObjectDataTrait;

    public function __construct(
        private HotspotImageAdapter $hotspotImageAdapter,
        private PatchServiceInterface $patchService
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?ImageGalleryData {
        $galleryData = $data[$key] ?? null;
        if (!is_array($galleryData) || !$fieldDefinition instanceof ImageGallery) {
            return null;
        }

        if ($isPatch) {
            $galleryData = $this->getPatchData($galleryData, $element, $fieldDefinition, $contextData);
        }

        $images = [];
        foreach ($galleryData as $item) {
            $images[] = $this->hotspotImageAdapter->getDataForSetter(
                $element,
                $fieldDefinition,
                $key,
                [$key => $item],
                $user,
                $contextData,
                $isPatch
            );
        }

        return new ImageGalleryData($images);
    }

    private function getPatchData(
        array $newData,
        Concrete $object,
        ImageGallery $fieldDefinition,
        ?FieldContextData $contextData
    ): array {
        $existingValues = $fieldDefinition->normalize(
            $this->getValidFieldValue($object, $fieldDefinition->getName(), $contextData)
        );

        if (!is_array($existingValues)) {
            return $newData;
        }

        $existingValues = $this->normalizeHotspotAndMarkerData($existingValues);

        return $this->patchService->handlePatchDataField($newData, $existingValues, SubTypes::IMAGE->value);
    }

    private function normalizeHotspotAndMarkerData(array $existingValues): array
    {
        foreach ($existingValues as $index => $image) {
            $image['hotspots'] = $this->normalizeNestedData($image['hotspots']);
            $image['marker'] = $this->normalizeNestedData($image['marker']);
            $existingValues[$index] = $image;
        }

        return $existingValues;
    }

    private function normalizeNestedData(array $items): array
    {
        foreach ($items as $index => $item) {
            if (!empty($item['data']) && is_array($item['data'])) {
                foreach ($item['data'] as $dataIndex => $dataItem) {
                    $item['data'][$dataIndex] = $this->hotspotImageAdapter->normalizeImageData($dataItem);
                }
            }
            $items[$index] = $item;
        }

        return $items;
    }
}
