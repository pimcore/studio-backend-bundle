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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Attribute\Response\Content\ContextPermissionsJson;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions\ContextPermissionsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
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
final class GetContextPermissionsController extends AbstractApiController
{
    private const string ROUTE = '/elements/{elementType}/context-permissions/';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ContextPermissionsServiceInterface $contextPermissionService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidElementTypeException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_elements_get_context_permissions', methods: ['GET'])]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'element_get_context_permissions',
        description: 'element_get_context_permissions_description',
        summary: 'element_get_context_permissions_summary',
        tags: [Tags::Elements->name]
    )]
    #[ElementTypeParameter]
    #[SuccessResponse(
        description: 'element_get_context_permissions_success_response',
        content: new ContextPermissionsJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getElementContextPermissions(string $elementType): JsonResponse
    {

        return $this->jsonResponse($this->contextPermissionService->list($elementType));
    }
}
