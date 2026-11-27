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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ThumbnailServiceInterface as AssetThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Hydrator\SettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Repository\SettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\ThumbnailPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;
use Pimcore\Model\Asset\Image;

/**
 * @internal
 */
final readonly class ThumbnailService implements ThumbnailServiceInterface
{
    private const int BACKGROUND_IMAGE_MAX_WIDTH = 1920;

    public function __construct(
        private AssetThumbnailServiceInterface $assetThumbnailService,
        private AssetResolverInterface $assetResolver,
        private SettingsHydratorInterface $settingsHydrator,
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    /**
     * @throws Exception
     */
    public function getThumbnails(): ThumbnailPaths
    {

        $adminConfig = $this->settingsHydrator->hydrate(
            $this->settingRepository->getConfiguration()
        );

        $customLogo = $adminConfig->getBranding()->getCustomLogo();
        $backgroundImage = $adminConfig->getBranding()->getLoginScreenCustomBackgroundImage();

        return new ThumbnailPaths(
            $this->getThumbnailPath(
                $customLogo,
                $this->getLogoParameters(299, 105)
            ),
            $this->getThumbnailPath(
                $customLogo,
                $this->getLogoParameters(586, 206)
            ),
            $this->getThumbnailPath(
                $backgroundImage,
                $this->getBackgroundImageParameters()
            )
        );
    }

    private function getLogoParameters(int $width, int $height): ImageDownloadConfigParameter
    {
        return new ImageDownloadConfigParameter(
            mimeType: 'png',
            width: $width,
            height: $height
        );
    }

    /**
     * The login background covers the whole screen, so it is not fitted into a box like the logo.
     * It is capped at a full HD width (smaller images are never scaled up) and delivered as JPEG:
     * a full-size PNG of a photo is several megabytes and can take longer to encode than the PHP
     * execution time allows, in which case the thumbnail is never stored and every visit of the
     * login screen starts generating it again.
     */
    private function getBackgroundImageParameters(): ImageDownloadConfigParameter
    {
        return new ImageDownloadConfigParameter(
            mimeType: MimeTypes::JPEG->value,
            resizeMode: ResizeModes::SCALE_BY_WIDTH,
            width: self::BACKGROUND_IMAGE_MAX_WIDTH
        );
    }

    private function getThumbnailPath(
        ?RelatedElementData $assetData,
        ImageDownloadConfigParameter $parameters
    ): ?string {

        if ($assetData === null) {
            return null;
        }

        $asset = $this->assetResolver->getById(
            $assetData->getId()
        );

        if (!$asset instanceof Image) {
            throw new InvalidElementTypeException($asset->getType());
        }

        $thumbnail = $this->assetThumbnailService->getThumbnailFromConfiguration(
            $asset,
            $parameters
        );

        return $thumbnail->getPath();
    }
}
