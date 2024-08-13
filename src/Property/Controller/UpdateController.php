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
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Attributes\Request\PredefinedPropertyRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PropertyServiceInterface $propertyService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route('/properties/{id}', name: 'pimcore_studio_api_update_property', methods: ['PUT'])]
    #[IsGranted(UserPermissions::PREDEFINED_PROPERTIES->value)]
    #[Put(
        path: self::API_PATH . '/properties/{id}',
        operationId: 'property_update',
        description: 'property_update_description',
        summary: 'property_update_summary',
        tags: [Tags::Properties->name]
    )]
    #[IdParameter(type: 'property', schema: new Schema(type: 'string', example: 'alpha-numerical'))]
    #[PredefinedPropertyRequestBody]
    #[SuccessResponse(
        description: 'property_update_success_response',
        content: new JsonContent(ref: PredefinedProperty::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateProperty(
        string $id,
        #[MapRequestPayload] UpdatePredefinedProperty $updatePredefinedProperty
    ): JsonResponse {
        $this->propertyService->updatePredefinedProperty($id, $updatePredefinedProperty);

        return $this->jsonResponse($this->propertyService->getPredefinedProperty($id));
    }
}
