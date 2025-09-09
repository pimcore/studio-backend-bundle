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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Document;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\DynamicAssetConfigRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DynamicConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DynamicStreamController extends AbstractApiController
{
    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly DownloadServiceInterface $downloadService,
        private readonly SecurityServiceInterface $securityService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws ThumbnailResizingFailedException
     * @throws UserNotFoundException
     */
    #[Route(
        '/assets/{id}/document/stream/dynamic',
        name: 'pimcore_studio_api_stream_document_dynamic',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . '/assets/{id}/document/stream/dynamic',
        operationId: 'asset_document_stream_dynamic',
        description: 'asset_document_stream_dynamic_description',
        summary: 'asset_document_stream_dynamic_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT)]
    #[DynamicAssetConfigRequestBody]
    #[SuccessResponse(
        description: 'asset_document_stream_dynamic_success_response',
        content: [new MediaType('image/*')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value)]
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function streamDynamicThumbnailConfig(
        int $id,
        #[MapRequestPayload] DynamicConfigurationParameter $parameter
    ): StreamedResponse {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->downloadService->streamDynamicDocumentThumbnail($asset, $parameter);
    }
}
