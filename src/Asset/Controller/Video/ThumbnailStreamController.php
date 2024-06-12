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

use League\Flysystem\FilesystemException;
use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Content\AssetMediaType;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attributes\Parameters\Path\ThumbnailNameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ThumbnailStreamController extends AbstractApiController
{
    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly BinaryServiceInterface $binaryService,
        private readonly SecurityServiceInterface $securityService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementNotFoundException
     * @throws ElementProcessingNotCompletedException
     * @throws FilesystemException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     * @throws UserNotFoundException
     */
    #[Route(
        '/assets/{id}/video/stream/{thumbnailName}',
        name: 'pimcore_studio_api_stream_video_thumbnail',
        methods: ['GET']
    )]
    //#[IsGranted('STUDIO_API')]
    //#[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets/{id}/video/stream/{thumbnailName}',
        operationId: 'streamVideoByThumbnail',
        description: 'Get video stream by id and thumbnail name by path parameter',
        summary: 'Get video stream by id and thumbnail name',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'video')]
    #[ThumbnailNameParameter]
    #[SuccessResponse(
        description: 'Video stream based on thumbnail name',
        content: new AssetMediaType('video/mp4'),
        headers: [new ContentDisposition('inline')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function streamVideoByThumbnail(int $id, string $thumbnailName): StreamedResponse
    {
        $asset = $this->assetService->getAssetElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->binaryService->streamVideoByThumbnail(
            $asset,
            $thumbnailName,
        );
    }
}
