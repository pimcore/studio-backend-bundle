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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller;

use Exception;
use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetKeyGroupRelationsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly KeyGroupRelationServiceInterface $keyGroupRelationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    #[Route(
        path: '/classification-store/key-group-relations',
        name: 'pimcore_studio_api_classification_store_get_key_group_relations',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/classification-store/key-group-relations',
        operationId: 'classification_store_get_key_group_relations',
        description: 'classification_store_get_key_group_relations_description',
        summary: 'classification_store_get_key_group_relations_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[SuccessResponse(
        description: 'classification_store_get_key_group_relations_response',
        content: new CollectionJson(new GenericCollection(KeyGroupRelation::class))
    )]
    #[IdParameter(
        description: 'Classification Store ID',
        namePrefix: 'store',
    )]
    #[TextFieldParameter(
        description: 'Class ID',
        name: 'classId',
        required: false,
        example: 'AP'
    )]
    #[TextFieldParameter(
        name: 'searchTerm',
        description: 'Search Term',
        required: false,
        example: 'search term'
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[TextFieldParameter(
        name: 'fieldName',
        description: 'Field Name',
        required: true,
        example: 'technicalAttributes'
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getKeyGroupRelations(
        #[MapQueryString] ListClassificationStoreParameter $parameters
    ): JsonResponse {
        $keyGroupRelations = $this->keyGroupRelationService->getKeyGroupRelations($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $keyGroupRelations->getItems(),
            $keyGroupRelations->getTotalItems()
        );
    }
}
