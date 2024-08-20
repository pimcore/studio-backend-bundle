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

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\Image\Thumbnail\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\FormatTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Image;
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
        private DocumentServiceInterface $documentService,
        private ConfigResolverInterface $configResolver,
        private VersionDetailServiceInterface $versionDetailService,
        private VersionRepositoryInterface $repository,
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

    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException
     */
    public function streamThumbnailImage(
        int $id,
        UserInterface $user
    ): StreamedResponse {
        $version = $this->repository->getVersionById($id);
        $image = $this->repository->getElementFromVersion($version, $user);
        if (!$image instanceof Image) {
            throw new InvalidElementTypeException($image->getType());
        }

        $config = $this->configResolver->getPreviewConfig();
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

    /**
     * @throws AccessDeniedException
     * @throws ElementProcessingNotCompletedException
     * @throws ElementStreamResourceNotFoundException
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UnprocessableContentException
     */
    public function streamPdfPreview(
        int $id,
        UserInterface $user
    ): StreamedResponse {
        $version = $this->repository->getVersionById($id);
        $document = $this->repository->getElementFromVersion($version, $user);

        if (!$document instanceof Document ||
            $document->getMimeType() !== MimeTypes::PDF->value
        ) {
            throw new InvalidElementTypeException($document->getType());
        }

        return $this->documentService->getPreviewStream($document);
    }
}
