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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Image;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\ImageConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\ImageCropParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\MimeTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\ResizeModeParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;
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
final class CustomStreamController extends AbstractApiController
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
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws UserNotFoundException
     */
    #[Route(
        '/assets/{id}/image/stream/custom',
        name: 'pimcore_studio_api_stream_image_custom',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/{id}/image/stream/custom',
        operationId: 'asset_image_stream_custom',
        description: 'asset_image_stream_custom_description',
        summary: 'asset_image_stream_custom_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'image')]
    #[MimeTypeParameter(MimeTypes::PNG->value)]
    #[ResizeModeParameter(
        true,
        [
            ResizeModes::SCALE_BY_HEIGHT,
            ResizeModes::SCALE_BY_WIDTH,
            ResizeModes::RESIZE,
            ResizeModes::NONE,
        ],
        ResizeModes::NONE
    )]
    #[ImageConfigParameter('width', 140)]
    #[ImageConfigParameter('height', 140)]
    #[ImageConfigParameter('quality', 85)]
    #[ImageConfigParameter('dpi', 72)]
    #[BoolParameter('contain', 'Contain', false, false)]
    #[BoolParameter('frame', 'Frame', false, false)]
    #[BoolParameter('cover', 'Cover', false, false)]
    #[BoolParameter('forceResize', 'Force resize', false, false)]
    #[BoolParameter('cropPercent', '', false, false)]
    #[ImageCropParameter('cropWidth', 0)]
    #[ImageCropParameter('cropHeight', 0)]
    #[ImageCropParameter('cropTop', 0)]
    #[ImageCropParameter('cropLeft', 0)]
    #[SuccessResponse(
        description: 'asset_image_stream_custom_success_response',
        content: [new MediaType('image/*')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value)]
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function streamCustomThumbnailConfig(
        int $id,
        #[MapQueryString] ImageDownloadConfigParameter $parameters
    ): StreamedResponse {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->binaryService->streamImageThumbnailFromConfig($asset, $parameters);
    }
}
