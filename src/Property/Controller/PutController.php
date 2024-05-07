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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Request\PropertyRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\BadRequestResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyHydratorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class PutController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PropertyServiceInterface $propertyService,
        private readonly PropertyHydratorServiceInterface $hydratorService,
    )
    {
        parent::__construct($serializer);
    }

    #[Route('/property', name: 'pimcore_studio_api_create_property', methods: ['PUT'])]
    #[Put(
        path: self::API_PATH . '/property',
        operationId: 'updateProperty',
        summary: 'Updating a property',
        security: self::SECURITY_SCHEME,
        tags: [Tags::Properties->name]
    )]
    #[PropertyRequestBody]
    #[SuccessResponse(
        description: 'Element Properties data as json',
        content: new JsonContent(ref: PredefinedProperty::class, type: 'object')
    )]
    #[BadRequestResponse]
    #[UnauthorizedResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function updateProperty(#[MapRequestPayload] UpdatePredefinedProperty $updatePredefinedProperty): JsonResponse
    {
        $property = $this->propertyService->updatePredefinedProperty($updatePredefinedProperty);
        return $this->jsonResponse($this->hydratorService->getHydratedPredefinedProperty($property));
    }
}