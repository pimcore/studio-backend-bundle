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

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\AssetPreviewDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Video as VideoAsset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Video as VideoData;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\UserInterface;
use Pimcore\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class VideoAdapter implements SetterDataInterface, DataNormalizerInterface, SearchPreviewDataInterface
{
    use AssetPreviewDataTrait;
    use ElementProviderTrait;

    public function __construct(
        private AssetResolverInterface $assetResolver,
        private DataServiceInterface $dataService
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
    ): ?Video {
        $adapterData = $data[$key] ?? null;
        if (!is_array($adapterData)) {
            return null;
        }

        $type = $adapterData['type'] ?? null;
        $poster = $adapterData['poster'] ?? null;
        if ($type === ElementTypes::TYPE_ASSET) {
            $adapterData['data'] = $this->resolveAssetIfNeeded($type, $adapterData['data']['id']);
            $adapterData['poster'] = $this->getAssetById($poster ? $poster['id'] : null);
        }

        return $this->createVideoObject($adapterData);
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$fieldDefinition instanceof VideoData || !$value instanceof VideoAsset) {
            return $data;
        }

        $key = $this->dataService->getPreviewFieldName($fieldDefinition);
        $data[$key] = $this->getSearchPreviewThumbnailPath($value);

        return $data;
    }

    private function resolveAssetIfNeeded(?string $type, ?int $id): ?Asset
    {
        return ($type === ElementTypes::TYPE_ASSET) ? $this->getAssetById($id) : null;
    }

    private function createVideoObject(array $adapterData): Video
    {
        $video = new Video();
        $video->setData($adapterData['data']);
        $video->setType($adapterData['type']);
        $video->setPoster($adapterData['poster'] ?? null);
        $video->setTitle($adapterData['title'] ?? null);
        $video->setDescription($adapterData['description'] ?? null);

        return $video;
    }

    private function getAssetById(?int $id): ?Asset
    {
        return $id ? $this->assetResolver->getById($id) : null;
    }

    public function normalize(mixed $value, Data $fieldDefinition): mixed
    {

        if (!$value instanceof Video || !$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $data = $fieldDefinition->normalize($value);

        if (isset($data['poster'])) {
            $data['poster']['fullPath'] = $value->getPoster()?->getRealFullPath();
        }

        $type = $data['type'] ?? '';
        if (isset($data['data']) && $type === ElementTypes::TYPE_ASSET) {
            $data['data']['fullPath'] = $value->getData()?->getRealFullPath();
            $data['data']['subtype'] = $value->getData()?->getType();
        }

        return $data;
    }
}
