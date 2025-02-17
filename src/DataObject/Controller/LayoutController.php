<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\LayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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

/**
 * @internal
 */
final class LayoutController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly LayoutServiceInterface $layoutService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    #[Route(
        path: '/data-objects/{id}/layout/{layoutId}',
        name: 'pimcore_studio_api_get_data_object_layout',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/data-objects/{id}/layout/{layoutId}',
        operationId: 'data_object_get_layout_by_id',
        description: 'data_object_get_layout_by_id_description',
        summary: 'data_object_get_layout_by_id_summary',
        tags: [Tags::DataObjects->value]
    )]
    #[IdParameter(type: 'data-object')]
    #[StringParameter(name: 'layoutId', example: 'Todo', description: 'ID to get specific layout', required: false)]
    #[SuccessResponse(
        description: 'data_object_get_layout_by_id_success_response',
        content: new JsonContent(ref: Layout::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getDataObjectLayoutById(int $id, ?string $layoutId = null): JsonResponse
    {
        return $this->jsonResponse($this->layoutService->getDataObjectLayout($id, $layoutId));
    }
}
