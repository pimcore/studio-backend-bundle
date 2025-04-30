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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PropertyServiceInterface $propertyService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotWriteableException
     */
    #[Route('/property', name: 'pimcore_studio_api_property_create', methods: ['POST'])]
    #[IsGranted(UserPermissions::PREDEFINED_PROPERTIES->value)]
    #[POST(
        path: self::PREFIX . '/property',
        operationId: 'property_create',
        description: 'property_create_description',
        summary: 'property_create_summary',
        tags: [Tags::Properties->name]
    )]
    #[SuccessResponse(
        description: 'property_create_success_response',
        content: new JsonContent(ref: PredefinedProperty::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createProperty(): JsonResponse
    {
        return $this->jsonResponse($this->propertyService->createPredefinedProperty());
    }
}
