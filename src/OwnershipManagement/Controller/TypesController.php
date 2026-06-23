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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\ConfigurationType;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\OwnershipManagementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TypesController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/ownership-management/types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly OwnershipManagementServiceInterface $ownershipManagementService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_ownership_management_types', methods: ['GET'])]
    #[IsGranted(UserPermissions::PIMCORE_ADMIN->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'ownership_management_get_types',
        description: 'ownership_management_get_types_description',
        summary: 'ownership_management_get_types_summary',
        tags: [Tags::OwnershipManagement->value]
    )]
    #[SuccessResponse(
        description: 'ownership_management_get_types_success_response',
        content: new CollectionJson(new GenericCollection(ConfigurationType::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
    ])]
    public function getTypes(): JsonResponse
    {
        $collection = $this->ownershipManagementService->getAvailableTypes();

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
