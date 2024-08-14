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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Attributes\Request\PropertyRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
        private readonly PropertyServiceInterface $propertyService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/properties', name: 'pimcore_studio_api_properties', methods: ['POST'])]
    #[Post(
        path: self::API_PATH . '/properties',
        operationId: 'property_get_collection',
        description: 'property_get_collection_description',
        summary: 'property_get_collection_summary',
        tags: [Tags::Properties->name]
    )]
    #[PropertyRequestBody]
    #[SuccessResponse(
        description: 'property_get_collection_success_response',
        content: new ItemsJson(PredefinedProperty::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getProperties(
        #[MapRequestPayload] PropertiesParameters $parameters
    ): JsonResponse {
        return $this->jsonResponse(['items' => $this->propertyService->getPredefinedProperties($parameters->getFilters())]);
    }
}
