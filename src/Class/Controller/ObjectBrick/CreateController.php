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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateObjectBrickParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CreateObjectBrick;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateController extends AbstractApiController
{
    private const string ROUTE = '/class/object-brick';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ObjectBrickServiceInterface $objectBrickService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementExistsException|ElementSavingFailedException|NotWriteableException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_object_brick_create',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::OBJECT_BRICKS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_object_brick_create',
        description: 'class_object_brick_create_description',
        summary: 'class_object_brick_create_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[ReferenceRequestBody(CreateObjectBrick::class)]
    #[SuccessResponse(
        description: 'class_object_brick_create_success_response',
        content: new JsonContent(ref: ObjectBrickDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createObjectBrick(
        #[MapRequestPayload] CreateObjectBrickParameters $parameters,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->objectBrickService->createObjectBrick($parameters)
        );
    }
}
