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
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\PermissionsToCheck;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassDefinitionServiceInterface $classDefinitionService
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/class/collection',
        name: 'pimcore_studio_api_classes_collection',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . '/class/collection',
        operationId: 'class_definition_collection',
        description: 'class_definition_collection_description',
        summary: 'class_definition_collection_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[SuccessResponse(
        description: 'class_definition_collection_success_response',
        content: new CollectionJson(new GenericCollection(ClassDefinitionList::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listClasses(): JsonResponse
    {
        $this->denyAccessUnlessGranted(
            'HasOneOf',
            new PermissionsToCheck([
                UserPermissions::CLASS_DEFINITION->value,
                UserPermissions::DATA_OBJECTS->value,
                UserPermissions::USER_MANAGEMENT->value,
            ])
        );

        $items = $this->classDefinitionService->getClassDefinitionCollection();

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items)
        );
    }
}
