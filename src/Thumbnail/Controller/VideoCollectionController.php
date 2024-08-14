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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Attributes\Response\Content\ThumbnailsJson;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository\ThumbnailRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class VideoCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly ThumbnailRepositoryInterface $repository,
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
        path: self::API_PATH . '/thumbnails/video',
        operationId: 'thumbnail_video_get_collection',
        description: 'thumbnail_video_get_collection_description',
        summary: 'thumbnail_video_get_collection_summary',
        tags: [Tags::AssetThumbnails->name]
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
