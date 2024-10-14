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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\UpdateAssetRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Response\Content\OneOfAssetJson;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\UpdateAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Put(
        path: self::PREFIX . '/assets/{id}',
        operationId: 'asset_update_by_id',
        description: 'asset_update_by_id_description',
        summary: 'asset_update_by_id_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET)]
    #[UpdateAssetRequestBody]
    #[SuccessResponse(
        description: 'One of asset types',
        content: new OneOfAssetJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function assetUpdateById(int $id, #[MapRequestPayload] UpdateAssetParameter $updateAsset): JsonResponse
    {
        $this->updateService->update(ElementTypes::TYPE_ASSET, $id, $updateAsset->getData());

        return $this->jsonResponse($this->assetService->getAsset($id));
    }
}
