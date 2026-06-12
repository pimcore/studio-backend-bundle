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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\CreateUnitParameters;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;
use Pimcore\Bundle\StudioBackendBundle\Unit\Service\QuantityValueServiceInterface;
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
    private const string ROUTE = '/unit/quantity-value/units';

    public function __construct(
        SerializerInterface $serializer,
        private readonly QuantityValueServiceInterface $quantityValueService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_unit_quantity_value_units_create', methods: ['POST'])]
    #[IsGranted(UserPermissions::QUANTITY_VALUE_UNITS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'unit_quantity_value_units_create',
        description: 'unit_quantity_value_units_create_description',
        summary: 'unit_quantity_value_units_create_summary',
        tags: [Tags::Units->value]
    )]
    #[ReferenceRequestBody(CreateUnitParameters::class)]
    #[SuccessResponse(
        description: 'unit_quantity_value_units_create_success_response',
        content: new JsonContent(ref: QuantityValueUnit::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function createUnit(#[MapRequestPayload] CreateUnitParameters $parameters): JsonResponse
    {
        return $this->jsonResponse($this->quantityValueService->createUnit($parameters));
    }
}
