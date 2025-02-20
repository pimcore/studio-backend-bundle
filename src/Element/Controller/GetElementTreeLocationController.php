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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\LocationData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementLocationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
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
final class GetElementTreeLocationController extends AbstractApiController
{
    private const string ROUTE = '/elements/{elementType}/location/{id}/{perspectiveId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementLocationServiceInterface $elementLocationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|InvalidQueryTypeException|InvalidFilterTypeException
     * @throws NotFoundException|NotWriteableException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_elements_get_element_tree_location', methods: ['GET'])]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'element_get_tree_location',
        description: 'element_get_tree_location_description',
        summary: 'element_get_tree_location_summary',
        tags: [Tags::Elements->name]
    )]
    #[IdParameter]
    #[ElementTypeParameter]
    #[StringParameter('perspectiveId', 'd061699e_da42_4075_b504_c2c93c687819', 'Get perspective by matching Id')]
    #[SuccessResponse(
        description: 'element_get_tree_location_success_response',
        content: new JsonContent(ref: LocationData::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getElementLocation(string $elementType, int $id, string $perspectiveId): JsonResponse
    {
        return $this->jsonResponse(
            $this->elementLocationService->getElementLocation(
                $elementType,
                $id,
                $perspectiveId
            )
        );
    }
}
