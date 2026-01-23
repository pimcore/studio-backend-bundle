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
use Pimcore\Model\Asset\Image;

/**
 * @internal
 */
final readonly class ThumbnailService implements ThumbnailServiceInterface
{
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
                299,
                105
            ),
            $this->getThumbnailPath(
                $customLogo,
                586,
                206
            ),
            $this->getThumbnailPath(
                $backgroundImage,
                586,
                206
            )
        );
    }

    private function getThumbnailPath(
        ?RelatedElementData $assetData = null,
        int $width = 299,
        int $height = 105
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
            new ImageDownloadConfigParameter(
                mimeType: 'png',
                width: $width,
                height: $height
            )
        );

        return $thumbnail->getPath();
    }
}
