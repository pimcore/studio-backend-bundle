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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Service\ImageThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/thumbnails/image/config/{name}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ImageThumbnailServiceInterface $imageThumbnailService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_thumbnails_image_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::THUMBNAILS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'thumbnail_image_delete',
        description: 'thumbnail_image_delete_description',
        summary: 'thumbnail_image_delete_summary',
        tags: [Tags::AssetThumbnails->value]
    )]
    #[StringParameter(
        name: 'name',
        example: 'content',
        description: 'Image thumbnail configuration name',
        required: true
    )]
    #[SuccessResponse(
        description: 'thumbnail_image_delete_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function deleteThumbnail(string $name): Response
    {
        $this->imageThumbnailService->deleteThumbnail($name);

        return new Response();
    }
}
