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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\DeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetServiceInterface $assetService,
        private readonly DeleteServiceInterface $deleteService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementDeletionFailedException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route('/assets/{id}/delete', name: 'pimcore_studio_api_assets_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Delete(
        path: self::API_PATH . '/assets/{id}/delete',
        operationId: 'deleteAsset',
        summary: 'Delete a specific asset and its children.',
        tags: [Tags::Assets->value]
    )]
    #[SuccessResponse(
        description: 'Successfully deleted asset',
    )]
    #[CreatedResponse(
        description: 'Successfully created jobRun for deleting assets',
        content: new IdJson('ID of created jobRun')
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET)]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteAsset(
        int $id
    ): Response {
        $user = $this->securityService->getCurrentUser();
        $asset = $this->assetService->getAssetElement(
            $user,
            $id
        );
        $status = HttpResponseCodes::SUCCESS->value;
        $data = null;
        $jobRunId = $this->deleteService->deleteAssets($asset, $user);

        if ($jobRunId) {
            $status = HttpResponseCodes::CREATED->value;

            return $this->jsonResponse(['id' => $jobRunId], $status);
        }

        return new Response($data, $status);
    }
}
