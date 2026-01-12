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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\StoreConfig;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\StoreServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class StoreCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/classification-store/config/collection';

    public function __construct(
        SerializerInterface $serializer,
        private readonly StoreServiceInterface $storeService
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_classification_store_get_config_collection',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_get_config_collection',
        description: 'classification_store_get_config_collection_description',
        summary: 'classification_store_get_config_collection_summary',
        tags: [Tags::ClassificationStore->value],
    )]
    #[SuccessResponse(
        description: 'classification_store_get_config_collection_success_response',
        content: new CollectionJson(new GenericCollection(StoreConfig::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getStoreCollection(): JsonResponse
    {
        $collection = $this->storeService->listStores();

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}

