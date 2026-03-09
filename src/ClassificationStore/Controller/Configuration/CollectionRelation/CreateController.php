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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\CollectionRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
final class CreateController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/collection-relations/add';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CollectionRelationServiceInterface $collectionRelationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_cs_configuration_collection_relation_create',
        methods: ['POST'],
        priority: 10
    )]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_collection_relation_create',
        description: 'classification_store_configuration_collection_relation_create_description',
        summary: 'classification_store_configuration_collection_relation_create_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[ReferenceRequestBody(CollectionRelationCreate::class)]
    #[SuccessResponse(
        description: 'classification_store_configuration_collection_relation_create_success_response',
        content: new JsonContent(ref: CollectionRelationDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function createCollectionRelation(
        #[MapRequestPayload] CollectionRelationCreate $parameters
    ): JsonResponse {
        return $this->jsonResponse(
            $this->collectionRelationService->createOrUpdateCollectionRelation($parameters)
        );
    }
}
