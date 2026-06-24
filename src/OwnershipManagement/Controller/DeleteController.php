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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\DeleteParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\OwnershipManagementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/ownership-management/{type}/configurations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly OwnershipManagementServiceInterface $ownershipManagementService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_ownership_management_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::PIMCORE_ADMIN->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'ownership_management_delete_configurations',
        description: 'ownership_management_delete_configurations_description',
        summary: 'ownership_management_delete_configurations_summary',
        tags: [Tags::OwnershipManagement->value]
    )]
    #[StringParameter(
        name: 'type',
        example: 'grid_configuration',
        description: 'Configuration type to delete from.'
    )]
    #[ReferenceRequestBody(DeleteParameter::class)]
    #[SuccessResponse(description: 'ownership_management_delete_configurations_success_response')]
    #[CreatedResponse(
        description: 'ownership_management_delete_configurations_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function deleteConfigurations(
        string $type,
        #[MapRequestPayload] DeleteParameter $parameter,
    ): Response {
        $jobRunId = $this->ownershipManagementService->delete($type, $parameter);

        if ($jobRunId) {

            return $this->jsonResponse(['jobRunId' => $jobRunId], HttpResponseCodes::CREATED->value);
        }

        return new Response();
    }
}
