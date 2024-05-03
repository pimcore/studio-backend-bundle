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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\NotFoundResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\DataProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyHydratorServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PropertyHydratorServiceInterface $hydratorService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/properties/{elementType}/{id}', name: 'pimcore_studio_api_get_properties', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/properties/{elementType}/{id}',
        operationId: 'getPropertiesByTypeAndId',
        description: 'Get properties based on the type and the id',
        summary: 'Get properties by type and ID',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Properties->name]
    )]
    #[ElementTypeParameter]
    #[IdParameter(type: 'element')]
    #[SuccessResponse(
        description: 'Element Properties data as json',
        content: new JsonContent(ref: DataProperty::class, type: 'object')
    )]
    #[UnauthorizedResponse]
    #[NotFoundResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getProperties(string $elementType, int $id): JsonResponse
    {
        return $this->jsonResponse($this->hydratorService->getHydratedPropertyForElement($elementType, $id));
    }
}
