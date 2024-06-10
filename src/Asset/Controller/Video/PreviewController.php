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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Video;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Path\ThumbnailNameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\VideoPreview;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\PreviewServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Model\Asset\Video;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PreviewController extends AbstractApiController
{
    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly PreviewServiceInterface $previewService,
        private readonly SecurityServiceInterface $securityService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementNotFoundException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     */
    #[Route(
        '/assets/{id}/video/preview/{thumbnailName}',
        name: 'pimcore_studio_api_download_image_format',
        methods: ['GET']
    )]
    //#[IsGranted('STUDIO_API')]
    //#[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets/{id}/video/preview/{thumbnailName}',
        operationId: 'getVideoPreviewByThumbnail',
        description: 'Get video preview data by id and thumbnail name by path parameters',
        summary: 'Get video preview by id and thumbnail name',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'video')]
    #[ThumbnailNameParameter]
    #[SuccessResponse(
        description: 'Preview data of the video based on the thumbnail name',
        content: new JsonContent(ref: VideoPreview::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getVideoPreviewByThumbnail(int $id, string $thumbnailName): JsonResponse
    {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        if (!$asset instanceof Video) {
            throw new InvalidElementTypeException($asset->getType());
        }

        return $this->jsonResponse(
            $this->previewService->getVideoPreview($asset, $thumbnailName)
        );
    }
}
