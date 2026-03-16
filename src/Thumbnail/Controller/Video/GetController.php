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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Controller\Video;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\VideoThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Service\VideoThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    private const string ROUTE = '/thumbnails/video/config/{name}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly VideoThumbnailServiceInterface $videoThumbnailService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_thumbnails_video_get_by_name', methods: ['GET'])]
    #[IsGranted(UserPermissions::THUMBNAILS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'thumbnail_video_get_by_name',
        description: 'thumbnail_video_get_by_name_description',
        summary: 'thumbnail_video_get_by_name_summary',
        tags: [Tags::AssetThumbnails->value]
    )]
    #[StringParameter(
        name: 'name',
        example: 'content',
        description: 'Video thumbnail configuration name',
        required: true
    )]
    #[SuccessResponse(
        description: 'thumbnail_video_get_by_name_success_response',
        content: new JsonContent(ref: VideoThumbnailConfigDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getThumbnailByName(string $name): JsonResponse
    {
        return $this->jsonResponse($this->videoThumbnailService->getThumbnail($name));
    }
}
