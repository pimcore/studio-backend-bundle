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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\AssetPreviewDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage as HotspotImageData;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function in_array;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class HotspotImageAdapter implements SetterDataInterface, SearchPreviewDataInterface
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

    public function normalizeImageData(MarkerHotspotItem $hotspotItem): array
    {
        return [
            'name' => $hotspotItem->getName(),
            'type' => $hotspotItem->getType(),
            'value' => $hotspotItem->getValue(),
        ];
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
        }

        return $data;
    }

    private function processMetaData(array $metaData): array
    {
        foreach ($metaData as &$item) {
            $item = new MarkerHotspotItem($item);
            if ($this->isValidItem($item)) {
                try {
                    $element = $this->getElement($this->serviceResolver, $item['type'], $item->getValue());
                } catch (NotFoundException) {
                    continue;
                }

                $item['value'] = $element;
            }
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
