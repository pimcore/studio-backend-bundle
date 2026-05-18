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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\CreateThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\VideoThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Service\VideoThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateController extends AbstractApiController
{
    private const string ROUTE = '/thumbnails/video/config';

    public function __construct(
        SerializerInterface $serializer,
        private readonly VideoThumbnailServiceInterface $videoThumbnailService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementExistsException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_thumbnails_video_create', methods: ['POST'])]
    #[IsGranted(UserPermissions::THUMBNAILS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'thumbnail_video_create',
        description: 'thumbnail_video_create_description',
        summary: 'thumbnail_video_create_summary',
        tags: [Tags::AssetThumbnails->value]
    )]
    #[ReferenceRequestBody(CreateThumbnailConfig::class)]
    #[SuccessResponse(
        description: 'thumbnail_video_create_success_response',
        content: new JsonContent(ref: VideoThumbnailConfigDetail::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createThumbnail(#[MapRequestPayload] CreateThumbnailConfig $parameters): JsonResponse
    {
        return $this->jsonResponse(
            $this->videoThumbnailService->addThumbnail($parameters->getName())
        );
    }
}
