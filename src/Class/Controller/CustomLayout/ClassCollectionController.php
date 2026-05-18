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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\ClassIdsParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\ClassIdsParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class ClassCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/class/custom-layout/collection';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_custom_layout_collection',
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_custom_layout_collection',
        description: 'class_custom_layout_collection_description',
        summary: 'class_custom_layout_collection_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[ClassIdsParameter]
    #[SuccessResponse(
        description: 'class_custom_layout_collection_success_response',
        content: new CollectionJson(new GenericCollection(CustomLayoutCompact::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getCustomLayoutsByClass(
        #[MapQueryString] ClassIdsParameters $parameters = new ClassIdsParameters()
    ): JsonResponse {
        $items = $this->customLayoutService->getCustomLayoutCollection(
            $parameters->getClassIdsArray()
        );

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items)
        );
    }
}
