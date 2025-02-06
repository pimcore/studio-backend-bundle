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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Attribute\Response\Content\OneOfContextPermissionsJson;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
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
        private readonly ElementServiceInterface $elementService
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
        content: new OneOfContextPermissionsJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getElementContextPermissions(string $elementType): JsonResponse
    {

        return $this->jsonResponse($this->elementService->getElementContextPermissions($elementType));
    }
}
