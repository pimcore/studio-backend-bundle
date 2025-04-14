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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\AssetPreviewDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidDataTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage as HotspotImageData;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function get_class;
use function in_array;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class HotspotImageAdapter implements
    SetterDataInterface,
    SearchPreviewDataInterface,
    DataNormalizerInterface
{
    use AssetPreviewDataTrait;
    use ElementProviderTrait;

    public function __construct(
        private DataServiceInterface $dataService,
        private ServiceResolverInterface $serviceResolver
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
    ): ?Hotspotimage {

        $data = $data[$key];
        $imageId = !empty($data['image']['id']) && $data['image']['id'] > 0 ? $data['image']['id'] : null;
        if ($imageId === null) {
            return null;
        }

        $data['marker'] = $this->processData($data['marker'] ?? []);
        $data['hotspots'] = $this->processData($data['hotspots'] ?? []);

        return new Hotspotimage(
            $imageId,
            $data['hotspots'],
            $data['marker'],
            $data['crop']
        );
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$fieldDefinition instanceof HotspotImageData || !$value instanceof Image) {
            return $data;
        }

        $data[$this->dataService->getPreviewFieldName($fieldDefinition)] = $this->getSearchPreviewThumbnailPath($value);

        return $data;
    }

    public function normalize(mixed $value, Data $fieldDefinition): mixed
    {
        if (!($fieldDefinition instanceof HotspotImageData)) {
            throw new InvalidDataTypeException(HotspotImageData::class, get_class($fieldDefinition));
        }

        $value = $fieldDefinition->normalize($value);
        if (!is_array($value)) {
            return null;
        }

        $id = $value['image']['id'] ?? null;
        $type = $value['image']['type'] ?? null;

        if ($id === null || $type === null) {
            return $value;
        }

        $value['image'] = $this->normalizeElementData($id, $type, $value['image']);
        $value['hotspots'] = $this->normalizeLocationData($value['hotspots']);
        $value['marker'] = $this->normalizeLocationData($value['marker']);

        return $value;
    }

    public function normalizeImageData(MarkerHotspotItem $hotspotItem): array
    {
        return [
            'name' => $hotspotItem->getName(),
            'type' => $hotspotItem->getType(),
            'value' => $hotspotItem->getValue(),
        ];
    }

    private function normalizeElementData(int $id, string $type, array $imageData): array
    {
        $element = $this->getElementData($id, $type);
        if ($element instanceof AbstractObject || $element instanceof Document) {
            $imageData['published'] = $element->isPublished();
        }
        $imageData['subtype'] = $element->getType();
        $imageData['fullPath']  = $element->getFullPath();

        return $imageData;
    }

    private function normalizeLocationData(array $locationData): array
    {
        foreach ($locationData as &$location) {
            $data = $location['data'] ?? null;
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $key => $item) {
                if (!($item instanceof MarkerHotspotItem)) {
                    continue;
                }

                $imageData = $this->normalizeImageData($item);
                $location['data'][$key] = $imageData;
                if ($this->isValidItem($item))
                {
                    $location['data'][$key] = $this->normalizeElementData(
                        $item->getValue(),
                        $item->getType(),
                        $imageData
                    );
                }
            }
        }

        return $locationData;
    }

    private function getElementData(int $id, string $type): Asset|Document|AbstractObject
    {
        $element = $this->serviceResolver->getElementById($type, $id);
        if ($element === null) {
            throw new NotFoundException($type, $id);
        }

        return $element;
    }

    private function processData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        foreach ($data as &$element) {
            if (isset($element['data']) && is_array($element['data']) && $element['data'] !== []) {
                $element['data'] = $this->processMetaData($element['data']);
            }

            $element['data'] = $element['data'] ?? [];
        }

        return $data;
    }

    private function processMetaData(array $metaData): array
    {
        foreach ($metaData as &$item) {
            $item = new MarkerHotspotItem($item);
        }

        return $metaData;
    }

    private function isValidItem(MarkerHotspotItem $item): bool
    {
        return in_array(
            $item['type'],
            [ElementTypes::TYPE_ASSET, ElementTypes::TYPE_DOCUMENT, ElementTypes::TYPE_OBJECT],
            true
        ) && $item->getValue();
    }
}
