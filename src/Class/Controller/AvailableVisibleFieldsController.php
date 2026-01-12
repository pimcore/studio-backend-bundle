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
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\AvailableVisibleFieldsParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\AvailableVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\ClassNamesParameter;
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
use function count;

/**
 * @internal
 */
final class AvailableVisibleFieldsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/class/definition/available-visible-fields';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassServiceInterface $classService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_available_visible_fields',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_get_available_visible_fields',
        description: 'class_get_available_visible_fields_description',
        summary: 'class_get_available_visible_fields_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[ClassNamesParameter]
    #[SuccessResponse(
        description: 'class_get_available_visible_fields_success_response',
        content: new CollectionJson(new GenericCollection(AvailableVisibleField::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getAvailableVisibleFields(
        #[MapQueryString] AvailableVisibleFieldsParameters $parameters
    ): JsonResponse {
        $availableFields = $this->classService->getAvailableVisibleFields($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $availableFields,
            count($availableFields)
        );
    }
}

