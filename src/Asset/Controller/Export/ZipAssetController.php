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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Export;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportAssetFileParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\MaxFileSizeExceededException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ZipAssetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ZipServiceInterface $zipService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException|StreamResourceNotFoundException
     * @throws EnvironmentException|MaxFileSizeExceededException
     */
    #[Route('/assets/export/zip/asset', name: 'pimcore_studio_api_asset_export_zip_asset', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . '/assets/export/zip/asset',
        operationId: 'asset_export_zip_asset',
        description: 'asset_export_zip_asset_description',
        summary: 'asset_export_zip_asset_summary',
        tags: [Tags::Assets->name]
    )]
    #[RequestBody(
        required: true,
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'assets',
                    description: 'Asset IDs to include in the zip',
                    type: 'array',
                    items: new Items(type: 'integer'),
                ),
                new Property(
                    property: 'parentId',
                    description: 'ID of the parent folder for relative path resolution in the zip. Defaults to root (1).',
                    type: 'integer',
                    example: 1,
                ),
            ],
            type: 'object',
        )
    )]
    #[CreatedResponse(
        description: 'asset_export_zip_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::MAX_FILE_SIZE_EXCEEDED,
    ])]
    public function assetExportZipAsset(
        #[MapRequestPayload] ExportAssetFileParameter $exportAssetFileParameter
    ): Response {
        return $this->jsonResponse(
            ['jobRunId' => $this->zipService->generateZipFileForAssets($exportAssetFileParameter)],
            HttpResponseCodes::CREATED->value
        );
    }
}
