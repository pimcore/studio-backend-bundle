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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValues;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\RelationVisibleFieldValuesRequest;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\RelationVisibleFieldValuesServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Visible field values of the generic many-to-many relation types. Their rows reference documents, assets
 * and objects of several classes at once, so the values are loaded per page of rows rather than through a
 * data object grid of one class.
 */
final class GetVisibleFieldValuesForRelationController extends AbstractApiController
{
    private const string ROUTE = '/data-object/grid/visible-field-values-for-relation';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RelationVisibleFieldValuesServiceInterface $relationVisibleFieldValuesService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|ParseException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_get_data_objects_grid_visible_field_values_for_relation',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'data_object_get_visible_field_values_for_relation',
        description: 'data_object_get_visible_field_values_for_relation_description',
        summary: 'data_object_get_visible_field_values_for_relation_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[ReferenceRequestBody(RelationVisibleFieldValuesRequest::class)]
    #[SuccessResponse(
        description: 'data_object_get_visible_field_values_for_relation_success_response',
        content: new JsonContent(
            required: ['items'],
            properties: [
                new Property(
                    property: 'items',
                    type: 'array',
                    items: new Items(ref: RelationVisibleFieldValues::class),
                ),
            ],
            type: 'object'
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getVisibleFieldValuesForRelation(
        #[MapRequestPayload] RelationVisibleFieldValuesRequest $request
    ): JsonResponse {
        return $this->jsonResponse([
            'items' => $this->relationVisibleFieldValuesService->getVisibleFieldValues(
                $request,
                $this->securityService->getCurrentUser()
            ),
        ]);
    }
}
