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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Video;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video\VideoType;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\VideoServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class TypeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/assets/video/types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly VideoServiceInterface $videoService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_assets_video_type', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_get_video_types',
        description: 'asset_get_video_types_description',
        summary: 'asset_get_video_types_summary',
        tags: [Tags::Assets->value]
    )]
    #[SuccessResponse(
        description: 'asset_get_video_types_success_response',
        content: new CollectionJson(new GenericCollection(VideoType::class))
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getVideoTypes(): JsonResponse
    {
        $assetTypes = $this->videoService->getVideoTypes();

        return $this->getPaginatedCollection(
            $this->serializer,
            $assetTypes,
            count($assetTypes)
        );
    }
}
