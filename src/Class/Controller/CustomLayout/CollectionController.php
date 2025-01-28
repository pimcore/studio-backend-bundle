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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout;

use Exception;
use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception|NotFoundException
     */
    #[Route(
        '/class/custom-layout/collection/{dataObjectClass}',
        name: 'pimcore_studio_api_class_custom_layout_collection',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . '/class/custom-layout/collection/{dataObjectClass}',
        operationId: 'class_custom_layout_collection',
        description: 'class_custom_layout_collection_description',
        summary: 'class_custom_layout_collection_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'dataObjectClass',
        example: 'CAR',
        description: 'class_custom_layout_collection_data_object_class',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_custom_layout_collection_success_response',
        content: new CollectionJson(new GenericCollection(CustomLayoutCompact::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getCustomLayouts(string $dataObjectClass): JsonResponse
    {
        $items = $this->customLayoutService->getCustomLayoutCollection($dataObjectClass);

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items)
        );
    }
}
