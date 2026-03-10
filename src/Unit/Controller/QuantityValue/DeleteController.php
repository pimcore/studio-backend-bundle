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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Unit\Service\QuantityValueServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/unit/quantity-value/units/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly QuantityValueServiceInterface $quantityValueService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_unit_quantity_value_units_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::QUANTITY_VALUE_UNITS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'unit_quantity_value_units_delete',
        description: 'unit_quantity_value_units_delete_description',
        summary: 'unit_quantity_value_units_delete_summary',
        tags: [Tags::Units->value]
    )]
    #[StringParameter(
        name: 'id',
        description: 'unit_quantity_value_units_delete_param_id',
        required: true,
        example: 'mm'
    )]
    #[SuccessResponse(
        description: 'unit_quantity_value_units_delete_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteUnit(string $id): Response
    {
        $this->quantityValueService->deleteUnit($id);

        return new Response();
    }
}
