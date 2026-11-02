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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\SubGroup;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\SubGroupServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/sub-groups/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SubGroupServiceInterface $subGroupService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     * @throws ConflictException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_sub_group_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_sub_group_delete',
        description: 'classification_store_configuration_sub_group_delete_description',
        summary: 'classification_store_configuration_sub_group_delete_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[IdParameter(type: 'sub group configuration')]
    #[SuccessResponse(
        description: 'classification_store_configuration_sub_group_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::CONFLICT,
    ])]
    public function deleteSubGroup(int $id): Response
    {
        $this->subGroupService->deleteSubGroup($id);

        return new Response();
    }
}
