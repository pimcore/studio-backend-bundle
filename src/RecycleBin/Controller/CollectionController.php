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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RecycleBin;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\RecycleBinServiceInterface;
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

    private const string ROUTE = '/recycle-bin/items';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RecycleBinServiceInterface $recycleBinService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_recycle_bin_collection', methods: ['POST'])]
    #[IsGranted(UserPermissions::RECYCLE_BIN->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'recycle_bin_get_collection',
        description: 'recycle_bin_get_collection_description',
        summary: 'recycle_bin_get_collection_summary',
        tags: [Tags::RecycleBin->value]
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
            '{"key":"date", "type":"date", "filterValue":{"operator": "on", "value": "08/20/2024"}},' .
            '{"key":"path", "type":"like", "filterValue": "/path/to/element"},' .
            '{"key":"type", "type":"equals", "filterValue": "asset"}'
            . ']',
        sortFilterExample: '{"key":"date", "direction":"DESC"}'
    )]
    #[SuccessResponse(
        description: 'recycle_bin_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(RecycleBin::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getRecycleBin(
        #[MapRequestPayload] CollectionFilterParameter $parameters
    ): JsonResponse {
        $collection = $this->recycleBinService->listRecycleBin($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
