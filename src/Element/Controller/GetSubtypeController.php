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
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Subtype;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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
final class GetSubtypeController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementServiceInterface $elementService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    #[Route(
        '/elements/{elementType}/subtype/{id}',
        name: 'pimcore_studio_api_elements_get_subtype',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . '/elements/{elementType}/subtype/{id}',
        operationId: 'element_get_subtype',
        description: 'element_get_subtype_description',
        summary: 'element_get_subtype_summary',
        tags: [Tags::Elements->name]
    )]
    #[IdParameter]
    #[ElementTypeParameter]
    #[SuccessResponse(
        description: 'element_get_subtype_success_response',
        content: new JsonContent(ref: Subtype::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getElementSubtype(
        int $id,
        string $elementType
    ): JsonResponse {

        return $this->jsonResponse(
            $this->elementService->getElementSubtype(new ElementParameters($elementType, $id))
        );
    }
}
