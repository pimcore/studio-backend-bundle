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
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Content\AssetMediaType;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Path\ThumbnailNameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SearchException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ThumbnailDownloadController extends AbstractApiController
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
     * @throws ElementNotFoundException
     * @throws InvalidElementTypeException
     * @throws SearchException
     * @throws UserNotFoundException
     */
    #[Route(
        '/assets/{id}/image/download/thumbnail/{thumbnailName}',
        name: 'pimcore_studio_api_download_image_thumbnail',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets/{id}/image/download/thumbnail/{thumbnailName}',
        operationId: 'downloadImageByThumbnail',
        description: 'Download image by id and thumbnail name by path parameter',
        summary: 'Download image by id and thumbnail name',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'image')]
    #[ThumbnailNameParameter]
    #[SuccessResponse(
        description: 'Image based on thumbnail name',
        content: new AssetMediaType(),
        headers: [new ContentDisposition()]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function downloadImageByThumbnail(int $id, string $thumbnailName): BinaryFileResponse
    {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->downloadService->downloadImageByThumbnail(
            $asset,
            $thumbnailName
        );
    }
}
