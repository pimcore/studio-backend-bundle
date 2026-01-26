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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Controller\Image;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\OpenApi\Attribute\Response\Property\AnyOfThumbnailConfigNodes;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Service\ImageThumbnailServiceInterface;
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
final class TreeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/thumbnails/image/tree';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ImageThumbnailServiceInterface $imageThumbnailService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_thumbnails_image_tree', methods: ['GET'])]
    #[IsGranted(UserPermissions::THUMBNAILS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'thumbnail_image_get_tree',
        description: 'thumbnail_image_get_tree_description',
        summary: 'thumbnail_image_get_tree_summary',
        tags: [Tags::AssetThumbnails->value]
    )]
    #[SuccessResponse(
        description: 'thumbnail_image_get_tree_success_response',
        content: new CollectionJson(new AnyOfThumbnailConfigNodes())
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getTree(): JsonResponse
    {
        $tree = $this->imageThumbnailService->getTree();

        return $this->getPaginatedCollection(
            $this->serializer,
            $tree,
            count($tree)
        );
    }
}
