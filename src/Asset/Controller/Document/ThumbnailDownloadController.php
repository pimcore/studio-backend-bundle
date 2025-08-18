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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\PageConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\ThumbnailNameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\PageConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ThumbnailDownloadController extends AbstractApiController
{
    private const string ROUTE = '/assets/{id}/document/download/thumbnail/{thumbnailName}';
    
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
     * @throws SearchException
     * @throws UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_download_document_thumbnail', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_document_download_by_thumbnail',
        description: 'asset_document_download_by_thumbnail_description',
        summary: 'asset_document_download_by_thumbnail_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'document')]
    #[PageConfigParameter]
    #[ThumbnailNameParameter]
    #[SuccessResponse(
        description: 'asset_document_download_by_thumbnail_success_response',
        content: new MediaType(),
        headers: [new ContentDisposition()]
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function downloadDocumentImageByThumbnail(
        int $id,
        string $thumbnailName,
        #[MapQueryString] ?PageConfigurationParameter $parameter,
    ): StreamedResponse
    {
        $asset = $this->assetService->getAssetElement($this->securityService->getCurrentUser(), $id);

        return $this->downloadService->getDocumentThumbnailByName(
            $asset,
            $thumbnailName,
            $parameter ? $parameter->getPage() : 1,
        );
    }
}
