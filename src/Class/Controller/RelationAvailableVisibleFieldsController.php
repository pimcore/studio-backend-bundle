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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\RelationVisibleFieldServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * Visible fields of the generic many-to-many relation types. The class editor edits unsaved field
 * settings, so the allowed types are sent along instead of being read from a stored definition.
 */
final class RelationAvailableVisibleFieldsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/class/definition/available-visible-fields/relation';

    public function __construct(
        SerializerInterface $serializer,
        private readonly RelationVisibleFieldServiceInterface $relationVisibleFieldService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_available_visible_fields_for_relation',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_get_available_visible_fields_for_relation',
        description: 'class_get_available_visible_fields_for_relation_description',
        summary: 'class_get_available_visible_fields_for_relation_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[ReferenceRequestBody(RelationVisibleFieldsConfiguration::class)]
    #[SuccessResponse(
        description: 'class_get_available_visible_fields_for_relation_success_response',
        content: new CollectionJson(new GenericCollection(RelationVisibleField::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getAvailableVisibleFieldsForRelation(
        #[MapRequestPayload] RelationVisibleFieldsConfiguration $configuration
    ): JsonResponse {
        $availableFields = $this->relationVisibleFieldService->getAvailableVisibleFields($configuration);

        return $this->getPaginatedCollection(
            $this->serializer,
            $availableFields,
            count($availableFields)
        );
    }
}
