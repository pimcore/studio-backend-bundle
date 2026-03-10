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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\CollectionRelation;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionRelationDelete;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\CollectionRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
    private const string ROUTE = '/classification-store/configuration/collection-relations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CollectionRelationServiceInterface $collectionRelationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_collection_relation_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_collection_relation_delete',
        description: 'classification_store_configuration_collection_relation_delete_description',
        summary: 'classification_store_configuration_collection_relation_delete_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[ReferenceRequestBody(CollectionRelationDelete::class)]
    #[SuccessResponse(
        description: 'classification_store_configuration_collection_relation_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function deleteCollectionRelation(
        #[MapRequestPayload] CollectionRelationDelete $parameters
    ): Response {
        $this->collectionRelationService->deleteCollectionRelation(
            $parameters->getColId(),
            $parameters->getGroupId()
        );

        return new Response();
    }
}
