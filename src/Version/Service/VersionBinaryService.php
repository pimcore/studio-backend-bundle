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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\FormatTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\StreamedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final readonly class VersionBinaryService implements VersionBinaryServiceInterface
{
    use ElementProviderTrait;
    use StreamedResponseTrait;

    public function __construct(
        private VersionDetailServiceInterface $versionDetailService,
        private VersionRepositoryInterface $repository
    ) {
    }

    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException
     */
    public function downloadAsset(
        int $id,
        UserInterface $user
    ): StreamedResponse {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);
        if (!$element instanceof Asset) {
            throw new InvalidElementTypeException($element->getType());
        }

        return $this->getStreamedResponse(
            $element,
            HttpResponseHeaders::ATTACHMENT_TYPE->value,
            [],
            $this->versionDetailService->getAssetFileSize($element) ?? $element->getFileSize()
        );
    }

    public function streamThumbnailImage(
        int $id,
        UserInterface $user
    ): StreamedResponse {
        $version = $this->repository->getVersionById($id);
        $image = $this->repository->getElementFromVersion($version, $user);
        if (!$image instanceof Asset\Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        $config = Config::getPreviewConfig();
        $thumbnail = $image->getThumbnail($config);

        $autoFormatConfigs = $config->getAutoFormatThumbnailConfigs();
        if ($autoFormatConfigs && $config->getFormat() === strtoupper(FormatTypes::SOURCE)) {
            $thumbnail = $image->getThumbnail(current($autoFormatConfigs));
        }

        return $this->getStreamedResponse(
            $thumbnail,
            HttpResponseHeaders::INLINE_TYPE->value,
            [],
            $thumbnail->getFileSize()
        );
    }
}
