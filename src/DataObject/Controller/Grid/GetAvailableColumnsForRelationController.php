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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\AvailableGridColumnForRelationParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationForRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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
final class GetAvailableColumnsForRelationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ColumnConfigurationForRelationServiceInterface $columnConfigurationService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|SearchException
     */
    #[Route(
        '/data-object/grid/available-columns-for-relation',
        name: 'pimcore_studio_api_get_data_objects_grid_available_columns_for_relation',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/data-object/grid/available-columns-for-relation',
        operationId: 'data_object_get_available_grid_columns_for_relation',
        description: 'data_object_get_available_grid_columns_for_relation_description',
        summary: 'data_object_get_available_grid_columns_for_relation_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[SuccessResponse(
        description: 'data_object_get_available_grid_columns_success_for_relation_response',
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'columns',
                    type: 'array',
                    items: new Items(ref: ColumnConfiguration::class),
                )],
        )
    )]
    #[StringParameter(
        name: 'classId',
        example: 'EV',
        description: 'Identifies the class name for which the columns should be retrieved.',
        required: false
    )]
    #[StringParameter(
        name: 'relationField',
        example: 'myRelationField',
        description: 'relationField as dot notation, e.g. "myBlock.mySubRelationField"',
        required: false,
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getDataObjectAvailableGridColumnsForRelation(
        #[MapQueryString] AvailableGridColumnForRelationParameter $parameter
    ): JsonResponse {
        $columns = $this->columnConfigurationService->getAvailableDataObjectColumnConfigurationForRelation(
            $parameter->getClassId(),
            $parameter->getRelationField(),
            $this->securityService->getCurrentUser()
        );

        return $this->jsonResponse([
            'columns' => $columns,
        ]);
    }
}
