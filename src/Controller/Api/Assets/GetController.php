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
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Config\Tags;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Response\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\AssetSearchServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetSearchServiceInterface $assetSearchService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/{id}', name: 'pimcore_studio_api_get_asset', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/assets/{id}',
        operationId: 'getAssetById',
        description: 'Get assets by id by path parameter',
        summary: 'Get assets by id',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'asset')]
    #[SuccessResponse(
        description: 'Paginated assets with total count as header param',
        content: new JsonContent(ref: Asset::class)
    )]
    #[UnauthorizedResponse]
    public function getAssetById(int $id): JsonResponse
    {
        return $this->jsonResponse($this->assetSearchService->getAssetById($id));
    }
}
