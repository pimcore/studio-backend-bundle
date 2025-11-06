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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\PhpCodeTransformer;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetPhpCodeTransformerController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly PhpCodeTransformerServiceInterface $phpCodeTransformerService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/data-objects/grid/transformers/services/phpcode',
        name: 'pimcore_studio_api_get_phpcode_transformers',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/data-objects/grid/transformers/services/phpcode',
        operationId: 'data_object_get_phpcode_transformers',
        description: 'data_object_get_phpcode_transformers_description',
        summary: 'data_object_get_phpcode_transformers_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[SuccessResponse(
        description: 'data_object_get_phpcode_transformers_success_response',
        content: new CollectionJson(new GenericCollection(PhpCodeTransformer::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getPhpCodeTransformers(): JsonResponse
    {
        $collection = $this->phpCodeTransformerService->getPhpCodeTransformers();

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
