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

use OpenApi\Attributes\Delete;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\MetadataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/metadata/predefined/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly MetadataServiceInterface $metadataService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_metadata_predefined_delete',
        methods: ['DELETE'],
    )]
    #[IsGranted(UserPermissions::ASSET_METADATA->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'metadata_predefined_delete',
        description: 'metadata_predefined_delete_description',
        summary: 'metadata_predefined_delete_summary',
        tags: [Tags::Metadata->value],
    )]
    #[IdParameter(type: 'metadata', schema: new Schema(type: 'string', example: 'alpha-numerical'))]
    #[SuccessResponse(
        description: 'metadata_predefined_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deletePredefinedMetadata(string $id): Response
    {
        $this->metadataService->deletePredefinedMetadata($id);

        return new Response();
    }
}
