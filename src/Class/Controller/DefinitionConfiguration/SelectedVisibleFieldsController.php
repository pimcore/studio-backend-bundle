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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\DefinitionConfiguration;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\RelationParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationForRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter as QueryStringParameter;
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
final class SelectedVisibleFieldsController extends AbstractApiController
{
    private const string ROUTE = '/class/definition/configuration-view/detail/{id}/selected-visible-fields';

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
        self::ROUTE,
        name: 'pimcore_studio_api_class_relation_selected_visible_fields',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_get_selected_visible_fields',
        description: 'class_get_selected_visible_fields_description',
        summary: 'class_get_selected_visible_fields_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[StringParameter(
        name: 'id',
        example: 'CAR',
        description: 'Class definition unique identifier',
        required: true
    )]
    #[QueryStringParameter(
        name: 'relationField',
        example: 'myRelationField',
        description: 'Relation field name for which the selected fields should be retrieved as dot notation.',
        required: false,
    )]
    #[SuccessResponse(
        description: 'class_get_selected_visible_fields_success_response',
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'columns',
                    type: 'array',
                    items: new Items(ref: ColumnConfiguration::class),
                )],
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getSelectedVisibleFields(
        string $id,
        #[MapQueryString] RelationParameter $parameter
    ): JsonResponse {
        $columns = $this->columnConfigurationService->getAvailableDataObjectColumnConfigurationForRelation(
            $id,
            $parameter->getRelationField(),
            $this->securityService->getCurrentUser()
        );

        return $this->jsonResponse([
            'columns' => $columns,
        ]);
    }
}
