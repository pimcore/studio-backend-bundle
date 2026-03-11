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
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\BasicStreamConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\ThumbnailNameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\ImageCropParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\MimeTypeStreamParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
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
final class ThumbnailStreamController extends AbstractApiController
{
    private const string ROUTE = '/assets/{id}/image/stream/thumbnail/{thumbnailName}';

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
    #[Route(self::ROUTE, name: 'pimcore_studio_api_stream_image_thumbnail', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_image_stream_by_thumbnail',
        description: 'asset_image_stream_by_thumbnail_description',
        summary: 'asset_image_stream_by_thumbnail_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'image')]
    #[ThumbnailNameParameter]
    #[BoolParameter('cropPercent', '', false, false)]
    #[ImageCropParameter('cropWidth', 0)]
    #[ImageCropParameter('cropHeight', 0)]
    #[ImageCropParameter('cropTop', 0)]
    #[ImageCropParameter('cropLeft', 0)]
    #[MimeTypeStreamParameter]
    #[SuccessResponse(
        description: 'asset_image_stream_by_thumbnail_success_response',
        content: [new MediaType('image/*')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value)]
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function streamImageByThumbnail(
        int $id,
        string $thumbnailName,
        #[MapQueryString] ?BasicStreamConfigParameter $parameter = null
    ): StreamedResponse {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->binaryService->streamImageByThumbnail($asset, $thumbnailName, $parameter);
    }
}
