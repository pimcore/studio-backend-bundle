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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Unit\Attribute\Parameter\Query\ValueParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertAllUnitsParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValues;
use Pimcore\Bundle\StudioBackendBundle\Unit\Service\ConversionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ConvertAllController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ConversionServiceInterface $conversionService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/unit/quantity-value/convert-all',
        name: 'pimcore_studio_api_unit_quantity_value_convert_all',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/unit/quantity-value/convert-all',
        operationId: 'unit_quantity_value_convert_all',
        description: 'unit_quantity_value_convert_all_description',
        summary: 'unit_quantity_value_convert_all_summary',
        tags: [Tags::Units->value]
    )]
    #[TextFieldParameter(
        name: 'fromUnitId',
        description: 'Id of the unit to convert from',
        required: true
    )]
    #[ValueParameter]
    #[SuccessResponse(
        description: 'unit_quantity_value_convert_all_success_response',
        content: new JsonContent(ref: ConvertedQuantityValues::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function convertAll(#[MapQueryString] ConvertAllUnitsParameter $parameters): JsonResponse
    {
        return $this->jsonResponse($this->conversionService->convertAllUnits($parameters));
    }
}
