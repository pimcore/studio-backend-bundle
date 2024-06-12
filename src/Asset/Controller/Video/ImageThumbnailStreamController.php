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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Video;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Content\AssetMediaType;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Query\BinaryConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Query\MimeTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Query\ResizeModeParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ImageThumbnailStreamController extends AbstractApiController
{
    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly BinaryServiceInterface $binaryService,
        private readonly SecurityServiceInterface $securityService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementNotFoundException
     * @throws InvalidElementTypeException
     * @throws ThumbnailResizingFailedException
     */
    #[Route(
        '/assets/{id}/video/stream/imageThumbnail',
        name: 'pimcore_studio_api_stream_video_image_thumbnail',
        methods: ['GET']
    )]
    //#[IsGranted('STUDIO_API')]
    //#[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets/{id}/video/stream/imageThumbnail',
        operationId: 'getVideoImageThumbnail',
        description: 'Get video image thumbnail by configuration and by id path parameter',
        summary: 'Get video image thumbnail by configuration',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'video')]
    #[MimeTypeParameter]
    #[ResizeModeParameter]
    #[BinaryConfigParameter('width', 'of video image thumbnail', 265)]
    #[BinaryConfigParameter('height', 'of video image thumbnail')]
    #[BinaryConfigParameter('aspectRatio', ' of downloaded image', false, 'boolean')]
    #[BinaryConfigParameter('frame', ' of downloaded image', false, 'boolean')]
    #[SuccessResponse(
        description: 'Custom image',
        content: [new AssetMediaType('image/jpeg'), new AssetMediaType('image/png')],
        headers: [new ContentDisposition()]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getVideoImageThumbnail(
        int $id,
        #[MapQueryString] VideoImageStreamConfigParameter $imageConfig
    ): StreamedResponse
    {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->binaryService->streamVideoImageThumbnail($asset, $imageConfig);
    }
}