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
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Attribute\Response\Content\ThumbnailsJson;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository\VideoThumbnailRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly VideoThumbnailRepositoryInterface $repository,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws UserNotFoundException
     */
    #[Route('/thumbnails/video', name: 'pimcore_studio_api_thumbnails_video', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[IsGranted(UserPermissions::THUMBNAILS->value)]
    #[Get(
        path: self::PREFIX . '/thumbnails/video',
        operationId: 'thumbnail_video_get_collection',
        description: 'thumbnail_video_get_collection_description',
        summary: 'thumbnail_video_get_collection_summary',
        tags: [Tags::AssetThumbnails->value]
    )]
    #[SuccessResponse(
        description: 'thumbnail_video_get_collection_success_response',
        content: new ThumbnailsJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getVideoThumbnails(): JsonResponse
    {
        $collection = $this->repository->listVideoThumbnails();

        return $this->jsonResponse(
            [
                'items' => $collection->getItems(),
            ]
        );
    }
}
