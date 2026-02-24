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
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrickUsageData;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UsagesController extends AbstractApiController
{
    private const string ROUTE = '/class/object-brick/{key}/usages';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ObjectBrickServiceInterface $objectBrickService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_class_object_brick_get_usages',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::OBJECT_BRICKS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_object_brick_get_usages',
        description: 'class_object_brick_get_usages_description',
        summary: 'class_object_brick_get_usages_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'key',
        example: 'MyObjectBrick',
        description: 'Object brick unique key',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_object_brick_get_usages_success_response',
        content: new ItemsJson(ObjectBrickUsageData::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getObjectBrickUsages(string $key): JsonResponse
    {
        return $this->jsonResponse(
            ['items' => $this->objectBrickService->getObjectBrickUsages($key)]
        );
    }
}
