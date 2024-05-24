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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Content\OneOfAssetJson;
use Pimcore\Bundle\StudioBackendBundle\Asset\Request\UpdateAsset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetServiceInterface $assetService,
        private readonly UpdateServiceInterface $updateService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/{id}', name: 'pimcore_studio_api_update_asset', methods: ['PUT'])]
    //#[IsGranted('STUDIO_API')]
        //#[IsGranted(UserPermissions::ASSETS->value)]
    #[Put(
        path: self::API_PATH . '/assets/{id}',
        operationId: 'updateAssetById',
        description: 'Update assets by id',
        summary: 'Update asset',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'asset')]
    #[RequestBody(
        description: 'Asset data',
        required: true,
        content: new JsonContent()

    )]
    #[SuccessResponse(
        description: 'One of asset types',
        content: new OneOfAssetJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateAsset(int $id, #[MapRequestPayload] UpdateAsset $updateAsset): JsonResponse
    {
        $this->updateService->update('asset', $id, $updateAsset->getData());
        return $this->jsonResponse($this->assetService->getAsset($id));
    }
}
