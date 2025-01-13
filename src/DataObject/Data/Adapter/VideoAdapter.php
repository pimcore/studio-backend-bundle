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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class VideoAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(private AssetResolverInterface $assetResolver)
    {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?Video {
        $adapterData = $data[$key] ?? null;
        if (!is_array($adapterData)) {
            return null;
        }

        $adapterData['data'] = $this->resolveAssetIfNeeded($adapterData['type'] ?? null, $adapterData['data']);
        if ($adapterData['data'] === null) {
            return null;
        }

        $adapterData['poster'] = $this->getAssetByPath($adapterData['poster'] ?? null);

        return $this->createVideoObject($adapterData);
    }

    private function resolveAssetIfNeeded(?string $type, ?string $path): ?Asset
    {
        return ($type === ElementTypes::TYPE_ASSET) ? $this->getAssetByPath($path) : null;
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

    private function getAssetByPath(?string $path): ?Asset
    {
        return $path ? $this->assetResolver->getByPath($path) : null;
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

        if (isset($data['data'])) {
            $data['data']['fullPath'] = $value->getData()?->getRealFullPath();
            $data['data']['subtype'] = $value->getData()?->getType();
        }

        return $data;
    }
}
