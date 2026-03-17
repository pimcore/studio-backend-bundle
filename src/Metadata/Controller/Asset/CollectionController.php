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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Controller\Asset;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\MetadataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    private const string ROUTE = '/metadata/asset';

    public function __construct(
        SerializerInterface $serializer,
        private readonly MetadataServiceInterface $metadataService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_metadata_asset_collection',
        methods: ['GET'],
        priority: 10,
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'metadata_asset_get_collection',
        description: 'metadata_asset_get_collection_description',
        summary: 'metadata_asset_get_collection_summary',
        tags: [Tags::Metadata->value],
    )]
    #[TextFieldParameter(
        name: 'subType',
        description: 'metadata_asset_get_collection_param_sub_type',
        required: false,
        example: 'image'
    )]
    #[TextFieldParameter(
        name: 'group',
        description: 'metadata_asset_get_collection_param_group',
        required: false,
        example: 'default'
    )]
    #[SuccessResponse(
        description: 'metadata_asset_get_collection_success_response',
        content: new ItemsJson(PredefinedMetadata::class)
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getAssetPredefinedMetadata(
        ?string $subType = null,
        ?string $group = null,
    ): JsonResponse {
        return $this->jsonResponse(
            ['items' => $this->metadataService->getAssetPredefinedMetadata($subType, $group)]
        );
    }
}
