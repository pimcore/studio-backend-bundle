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

use Exception;
use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\LayoutDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

final class LayoutController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly LayoutDefinitionServiceInterface $layoutDefinitionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception|InvalidElementTypeException|NotFoundException
     */
    #[Route(
        '/class/object-brick/{objectId}/object/layout',
        name: 'pimcore_studio_api_class_object_brick_object_layout',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . '/class/object-brick/{objectId}/object/layout',
        operationId: 'class_object_brick_object_layout',
        description: 'class_object_brick_object_layout_description',
        summary: 'class_object_brick_object_layout_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[IdParameter(name: 'objectId', required: true)]
    #[SuccessResponse(
        description: 'class_object_brick_object_layout_success_response',
        content: new CollectionJson(new GenericCollection(LayoutDefinition::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function getObjectBrickLayoutForObject(int $objectId): JsonResponse
    {
        $items = $this->layoutDefinitionService->getLayoutDefinitionsForObject($objectId);

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items),
        );
    }
}
