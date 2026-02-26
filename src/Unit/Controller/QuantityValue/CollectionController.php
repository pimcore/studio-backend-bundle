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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Controller\QuantityValue;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request\CollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;
use Pimcore\Bundle\StudioBackendBundle\Unit\Service\QuantityValueServiceInterface;
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

    private const string ROUTE = '/unit/quantity-value/units/collection';

    public function __construct(
        SerializerInterface $serializer,
        private readonly QuantityValueServiceInterface $quantityValueService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_unit_quantity_value_units_collection', methods: ['POST'], priority: 10)]
    #[IsGranted(UserPermissions::QUANTITY_VALUE_UNITS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'unit_quantity_value_units_collection',
        description: 'unit_quantity_value_units_collection_description',
        summary: 'unit_quantity_value_units_collection_summary',
        tags: [Tags::Units->value]
    )]
    #[CollectionRequestBody]
    #[SuccessResponse(
        description: 'unit_quantity_value_units_collection_success_response',
        content: new CollectionJson(new GenericCollection(QuantityValueUnit::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getUnitCollection(
        #[MapRequestPayload] CollectionFilterParameter $parameters
    ): JsonResponse {
        $collection = $this->quantityValueService->listUnitCollection($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
