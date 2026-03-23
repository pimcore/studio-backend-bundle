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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Attribute\Request\PredefinedMetadataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\UpdatePredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\MetadataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
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
    private const string ROUTE = '/metadata/predefined/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly MetadataServiceInterface $metadataService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementExistsException
     * @throws NotFoundException
     * @throws NotWriteableException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_metadata_predefined_update',
        methods: ['PUT'],
    )]
    #[IsGranted(UserPermissions::ASSET_METADATA->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'metadata_predefined_update',
        description: 'metadata_predefined_update_description',
        summary: 'metadata_predefined_update_summary',
        tags: [Tags::Metadata->value],
    )]
    #[IdParameter(type: 'metadata', schema: new Schema(type: 'string', example: 'alpha-numerical'))]
    #[PredefinedMetadataRequestBody]
    #[SuccessResponse(
        description: 'metadata_predefined_update_success_response',
        content: new JsonContent(ref: PredefinedMetadata::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updatePredefinedMetadata(
        string $id,
        #[MapRequestPayload] UpdatePredefinedMetadata $updateMetadata,
    ): JsonResponse {
        return $this->jsonResponse($this->metadataService->updatePredefinedMetadata($id, $updateMetadata));
    }
}
