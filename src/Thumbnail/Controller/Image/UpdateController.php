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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ImageThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Service\ImageThumbnailServiceInterface;
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
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/thumbnails/image/config/{name}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ImageThumbnailServiceInterface $imageThumbnailService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|InvalidArgumentException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_thumbnails_image_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::THUMBNAILS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'thumbnail_image_update',
        description: 'thumbnail_image_update_description',
        summary: 'thumbnail_image_update_summary',
        tags: [Tags::AssetThumbnails->value]
    )]
    #[StringParameter(
        name: 'name',
        example: 'content',
        description: 'Image thumbnail configuration name',
        required: true
    )]
    #[ReferenceRequestBody(UpdateThumbnailConfig::class)]
    #[SuccessResponse(
        description: 'thumbnail_image_update_success_response',
        content: new JsonContent(ref: ImageThumbnailConfigDetail::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateThumbnail(
        string $name,
        #[MapRequestPayload] UpdateThumbnailConfig $parameters
    ): JsonResponse {
        return $this->jsonResponse(
            $this->imageThumbnailService->updateThumbnail($name, $parameters)
        );
    }
}
