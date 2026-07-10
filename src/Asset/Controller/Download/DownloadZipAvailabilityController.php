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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Download;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\DownloadAvailability;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DownloadZipAvailabilityController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly DownloadServiceInterface $downloadService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException
     */
    #[Route(
        '/assets/download/zip/{jobRunId}/available',
        name: 'pimcore_studio_api_zip_download_asset_available',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/download/zip/{jobRunId}/available',
        operationId: 'asset_download_zip_available',
        description: 'asset_download_zip_available_description',
        summary: 'asset_download_zip_available_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'JobRun', name: 'jobRunId')]
    #[SuccessResponse(
        description: 'asset_download_zip_available_success_response',
        content: new JsonContent(ref: DownloadAvailability::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function availableZip(int $jobRunId): JsonResponse
    {
        $available = $this->downloadService->isResourceAvailableByJobRunId(
            $jobRunId,
            ZipServiceInterface::DOWNLOAD_ZIP_FILE_NAME,
            ZipServiceInterface::DOWNLOAD_ZIP_FOLDER_NAME,
        );

        return $this->jsonResponse(
            new DownloadAvailability($available),
            headers: [HttpResponseHeaders::HEADER_CACHE_CONTROL->value => 'no-store'],
        );
    }
}
