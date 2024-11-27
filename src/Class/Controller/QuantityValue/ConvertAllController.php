<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\QuantityValue;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Request\ConvertRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConvertAllParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConvertedQuantityValues;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\QuantityValueServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ConvertAllController extends AbstractApiController
{
    use ElementProviderTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly QuantityValueServiceInterface $quantityValueService

    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException|DatabaseException|NotFoundException|UserNotFoundException
     */
    #[Route(
        '/class/quantity-value/convert-all',
        name: 'pimcore_studio_api_get_class_quantity_value_convert_all',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/class/quantity-value/convert-all',
        operationId: 'class_quantity_value_unit_convert_all',
        description: 'class_quantity_value_unit_convert_all_description',
        summary: 'class_quantity_value_unit_convert_all_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[SuccessResponse(
        description: 'class_quantity_value_unit_convert_all_success_response',
        content: new JsonContent(ref: ConvertedQuantityValues::class)
    )]
    #[ConvertRequestBody(ConvertAllParameters::class)]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function get(#[MapRequestPayload] ConvertAllParameters $parameters): JsonResponse
    {
        return $this->jsonResponse($this->quantityValueService->convertAllUnits($parameters));
    }
}
