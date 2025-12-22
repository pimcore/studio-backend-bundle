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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutIdentifierData;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\IdentifierServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class GetIdentifierController extends AbstractApiController
{
    private const string ROUTE = '/class/custom-layout/identifier-data/{classDefinitionId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly IdentifierServiceInterface $identifierService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_custom_layout_get_identifier_data',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_custom_layout_get_identifier_data',
        description: 'class_custom_layout_get_identifier_data_description',
        summary: 'class_custom_layout_get_identifier_data_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'classDefinitionId',
        example: 'CAR',
        description: 'Class definition unique identifier for custom layouts',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_custom_layout_get_identifier_data_success_response',
        content: new JsonContent(ref: CustomLayoutIdentifierData::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCustomLayoutIdentifierData(string $classDefinitionId): JsonResponse
    {
        return $this->jsonResponse(
            $this->identifierService->getCustomLayoutIdentifierData($classDefinitionId)
        );
    }
}

