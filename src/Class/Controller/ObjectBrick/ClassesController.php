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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class ClassesController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/class/object-brick/classes';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_object_brick_classes',
        methods: ['GET'],
        priority: 10
    )]
    #[IsGranted(UserPermissions::OBJECT_BRICKS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_object_brick_classes',
        description: 'class_object_brick_classes_description',
        summary: 'class_object_brick_classes_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[SuccessResponse(
        description: 'class_object_brick_classes_success_response',
        content: new CollectionJson(new GenericCollection(ClassDefinitionList::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getClassesWithObjectBricks(): JsonResponse
    {
        $items = $this->classDefinitionService->getClassDefinitionsWithObjectBricks();

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items)
        );
    }
}
