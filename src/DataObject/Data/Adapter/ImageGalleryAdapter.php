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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataExportInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\SubTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ImageGallery as ImageGalleryData;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionAbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstractData;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ImageGalleryAdapter implements
    SetterDataInterface,
    DataNormalizerInterface,
    DataExportInterface
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

    public function normalize(mixed $value, Data $fieldDefinition): ?array
    {
        if (!$value instanceof ImageGalleryData) {
            return null;
        }

        $images = [];
        $items = $value->getItems();
        $itemsDefinition = new Hotspotimage();
        foreach ($items as $item) {
            $images[] = $this->hotspotImageAdapter->normalize($item, $itemsDefinition);
        }

        return $images;
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
            $image['hotspots'] = $this->hotspotImageAdapter->normalizeLocationData($image['hotspots']);
            $image['marker'] = $this->hotspotImageAdapter->normalizeLocationData($image['marker']);
            $existingValues[$index] = $image;
        }

        return $existingValues;
    }

    /**
     * @throws Exception
     */
    public function getExportData(
        Concrete $object,
        Data $fieldDefinition,
        string $key,
        ?FieldContextData $contextData = null
    ): string {
        $data = $this->getValidFieldValue($object, $key, $contextData);

        if (!$data instanceof ImageGalleryData) {
            return '';
        }

        $items = $data->getItems();
        $paths = array_map(
            static fn ($item) => $item->getImage()?->getFrontendFullPath(),
            $items
        );

        return implode(', ', array_filter($paths));
    }
}
