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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Image;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Content\AssetMediaType;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\ImageConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\MimeTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\ResizeModeParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ThumbnailResizingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CustomDownloadController extends AbstractApiController
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
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws ThumbnailResizingFailedException
     * @throws UserNotFoundException
     */
    #[Route(
        '/assets/{id}/image/download/custom',
        name: 'pimcore_studio_api_download_image_custom',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/{id}/image/download/custom',
        operationId: 'asset_image_download_custom',
        description: 'asset_image_download_custom_description',
        summary: 'asset_image_download_custom_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'image')]
    #[MimeTypeParameter]
    #[ResizeModeParameter]
    #[ImageConfigParameter('width', 140)]
    #[ImageConfigParameter('height')]
    #[ImageConfigParameter('quality')]
    #[ImageConfigParameter('dpi')]
    #[SuccessResponse(
        description: 'asset_image_download_custom_success_response',
        content: [new AssetMediaType('image/jpeg'), new AssetMediaType('image/png')],
        headers: [new ContentDisposition()]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function downloadCustomImage(
        int $id,
        #[MapQueryString] ImageDownloadConfigParameter $parameters
    ): BinaryFileResponse {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->downloadService->downloadCustomImage($asset, $parameters);
    }
}
