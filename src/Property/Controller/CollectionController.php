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
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\BadRequestResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyHydratorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly PropertyHydratorServiceInterface $hydratorService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidQueryTypeException
     */
    #[Route('/properties', name: 'pimcore_studio_api_properties', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[GET(
        path: self::API_PATH . '/properties',
        operationId: 'getProperties',
        description: 'Get paginated predefined properties',
        summary: 'Get all properties for element type',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Properties->name]
    )]
    #[ElementTypeParameter]
    #[SuccessResponse(
        description: 'Paginated properties with total count as header param',
        content: new ItemsJson(PredefinedProperty::class)
    )]
    #[BadRequestResponse]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getProperties(#[MapQueryString] PropertiesParameters $parameters): JsonResponse
    {
        return $this->jsonResponse($this->hydratorService->getHydratedProperties($parameters));
    }
}