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

use OpenApi\Attributes\Patch;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Request\PatchAssetRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\PatchAssetParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PatchController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PatchServiceInterface $patchService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException|ElementSavingFailedException
     * @throws NotFoundException|UserNotFoundException|InvalidArgumentException
     */
    #[Route('/assets', name: 'pimcore_studio_api_patch_asset', methods: ['PATCH'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Patch(
        path: self::API_PATH . '/assets',
        operationId: 'patchAssetById',
        description: 'patch_asset_by_id_description',
        summary: 'patch_asset_by_id_summary',
        tags: [Tags::Assets->name]
    )]
    #[PatchAssetRequestBody]
    #[SuccessResponse(
        description: 'patch_asset_by_id_success_response',
    )]
    #[CreatedResponse(
        description: 'patch_asset_by_id_created_response',
        content: new IdJson('ID of created jobRun')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function patchAssets(#[MapRequestPayload] PatchAssetParameter $patchAssetParameter): Response
    {
        $status = HttpResponseCodes::SUCCESS->value;
        $data = null;
        $jobRunId = $this->patchService->patch(
            ElementTypes::TYPE_ASSET,
            $patchAssetParameter->getData(),
            $this->securityService->getCurrentUser()
        );

        if ($jobRunId) {
            $status = HttpResponseCodes::CREATED->value;

            return $this->jsonResponse(['id' => $jobRunId], $status);
        }

        return new Response($data, $status);
    }
}
