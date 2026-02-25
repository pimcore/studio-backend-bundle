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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\FieldsByTypeParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldByType;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldsByTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class FieldsByTypeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/class/definition/fields-by-type';

    public function __construct(
        SerializerInterface $serializer,
        private readonly FieldsByTypeServiceInterface $fieldsByTypeService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_fields_by_type',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_get_fields_by_type',
        description: 'class_get_fields_by_type_description',
        summary: 'class_get_fields_by_type_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'classId',
        example: 'EV',
        description: 'The class ID to retrieve fields for.',
        required: true
    )]
    #[StringParameter(
        name: 'type',
        example: 'manyToOneRelation,objectbricks',
        description: 'Comma-separated list of field types to filter by.',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_get_fields_by_type_success_response',
        content: new CollectionJson(new GenericCollection(FieldByType::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getFieldsByType(
        #[MapQueryString] FieldsByTypeParameters $parameters
    ): JsonResponse {
        $fields = $this->fieldsByTypeService->getFieldsByType($parameters);

        return $this->getPaginatedCollection($this->serializer, $fields, count($fields));
    }
}
