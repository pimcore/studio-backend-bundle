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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api\Assets;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\PathParameter;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Unauthorized;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetSearchServiceInterface $assetSearchService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/{id}', name: 'pimcore_studio_api_get_asset', methods: ['GET'])]
    #[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/assets/{id}',
        description: 'Get paginated assets',
        summary: 'Get all assets',
        security: [
            [
                'auth_token' => []
            ]
        ],
        tags: ['Assets']
    )]
    #[PathParameter(
        name: 'id',
        description: 'Id of the asset',
        in: 'path',
        required: true,
        schema: new Schema(type: 'integer', example: 83),
        example: 83
    )]
    #[Response(
        response: 200,
        description: 'Paginated assets with total count as header param',
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Asset::class, type: 'object')
            )
        ]
    )]
    #[Response(
        response: 403,
        description: 'Unauthorized',
        content:[
            new MediaType(
                mediaType: 'application/json',
                schema: new Schema(ref: Unauthorized::class, type: 'object')
            )
        ]
    )]
    public function getAssets(int $id): JsonResponse
    {
        return $this->jsonResponse($this->assetSearchService->getAssetById($id));
    }
}
