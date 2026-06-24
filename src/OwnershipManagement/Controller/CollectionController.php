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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\OwnershipManagementServiceInterface;
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

    private const string ROUTE = '/ownership-management/{type}/configurations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly OwnershipManagementServiceInterface $ownershipManagementService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_ownership_management_get_collection', methods: ['POST'])]
    #[IsGranted(UserPermissions::PIMCORE_ADMIN->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'ownership_management_get_collection',
        description: 'ownership_management_get_collection_description',
        summary: 'ownership_management_get_collection_summary',
        tags: [Tags::OwnershipManagement->value]
    )]
    #[StringParameter(
        name: 'type',
        example: 'grid_configuration',
        description: 'Configuration type to list.'
    )]
    #[CollectionRequestBody(
        columnFiltersExample: '[' .
            '{"type":"search", "filterValue": "search term"},' .
            '{"type":"includeDeletedOwners", "filterValue": true}'
            . ']',
        sortFilterExample: '{"key":"modificationDate", "direction":"DESC"}'
    )]
    #[SuccessResponse(
        description: 'ownership_management_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(OwnershipConfiguration::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function getConfigurations(
        string $type,
        #[MapRequestPayload] CollectionFilterParameter $parameters,
    ): JsonResponse {
        $collection = $this->ownershipManagementService->listConfigurations($type, $parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
