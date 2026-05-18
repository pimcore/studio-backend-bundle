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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Collection;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\CollectionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IntParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/classification-store/configuration/stores/{storeId}/collections';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CollectionServiceInterface $collectionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_collection', methods: ['POST'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_collection_collection',
        description: 'classification_store_configuration_collection_collection_description',
        summary: 'classification_store_configuration_collection_collection_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[IntParameter(name: 'storeId', example: 1, description: 'ID of the store to list collections for')]
    #[CollectionRequestBody(
        columnFiltersExample: '[{"type":"search","filterValue":"my-collection"}]',
        sortFilterExample: '{"key":"name","direction":"ASC"}'
    )]
    #[SuccessResponse(
        description: 'classification_store_configuration_collection_collection_success_response',
        content: new CollectionJson(new GenericCollection(CollectionDetail::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCollections(
        int $storeId,
        #[MapRequestPayload] CollectionFilterParameter $parameters,
    ): JsonResponse {
        $collection = $this->collectionService->listCollections($parameters, $storeId);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
