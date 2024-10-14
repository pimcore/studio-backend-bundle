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
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Content\AssetMediaType;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\VideoImageStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\AspectRatioParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\AsyncGenerationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\FrameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\HeightParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\WidthParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
     * @throws ElementStreamResourceNotFoundException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailConfigurationException
     * @throws InvalidThumbnailException
     * @throws UserNotFoundException
     */
    #[Route(
        '/assets/{id}/video/stream/image-thumbnail',
        name: 'pimcore_studio_api_stream_video_image_thumbnail',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/{id}/video/stream/image-thumbnail',
        operationId: 'asset_video_image_thumbnail_stream',
        description: 'asset_video_image_thumbnail_stream_description',
        summary: 'asset_video_image_thumbnail_stream_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'video')]
    #[WidthParameter('Width of the video image thumbnail', 265)]
    #[HeightParameter('Height of the video image thumbnail')]
    #[AspectRatioParameter]
    #[FrameParameter]
    #[AsyncGenerationParameter]
    #[SuccessResponse(
        description: 'asset_video_image_thumbnail_stream_success_response',
        content: [new AssetMediaType('image/*')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value)]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getVideoImageThumbnail(
        int $id,
        #[MapQueryString] ?VideoImageStreamConfigParameter $imageConfig
    ): StreamedResponse {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );
        if (!$imageConfig) {
            $imageConfig = new VideoImageStreamConfigParameter();
        }

        return $this->binaryService->streamVideoImageThumbnail($asset, $imageConfig);
    }
}
