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
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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

final class GetController extends AbstractApiController
{
    private const string ROUTE = '/class/definition/configuration-view/detail/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_definition_get_by_id',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_definition_get_by_id',
        description: 'class_definition_get_by_id_description',
        summary: 'class_definition_get_by_id_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'id',
        example: 'CAR',
        description: 'Class definition unique identifier',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_definition_get_by_id_success_response',
        content: new JsonContent(ref: ClassDefinition::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getClassDefinitionById(string $id): JsonResponse
    {
        return $this->jsonResponse(
            $this->classDefinitionService->getClassDefinitionById($id)
        );
    }
}
