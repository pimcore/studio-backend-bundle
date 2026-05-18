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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Attribute\Request\MetadataCollectionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\MetadataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/metadata';

    public function __construct(
        SerializerInterface $serializer,
        private readonly MetadataServiceInterface $metadataService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_metadata',
        methods: ['POST'],
    )]
    #[IsGranted(UserPermissions::ASSET_METADATA->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'metadata_get_collection',
        description: 'metadata_get_collection_description',
        summary: 'metadata_get_collection_summary',
        tags: [Tags::Metadata->value],
    )]
    #[MetadataCollectionRequestBody]
    #[SuccessResponse(
        description: 'metadata_get_collection_success_response',
        content: new CollectionJson(new GenericCollection(PredefinedMetadata::class)),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getMetadata(
        #[MapRequestPayload] MetadataParameters $parameters = new MetadataParameters()
    ): JsonResponse {
        $collection = $this->metadataService->getPredefinedMetadata($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems(),
        );
    }
}
