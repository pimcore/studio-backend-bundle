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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Controller\Xlsx;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\DownloadAvailability;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFile;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AvailabilityController extends AbstractApiController
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
        '/export/download/xlsx/{jobRunId}/available',
        name: 'pimcore_studio_api_export_download_xlsx_available',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . '/export/download/xlsx/{jobRunId}/available',
        operationId: 'export_download_xlsx_available',
        description: 'export_download_xlsx_available_description',
        summary: 'export_download_xlsx_available_summary',
        tags: [Tags::Export->value]
    )]
    #[IdParameter(type: 'JobRun', name: 'jobRunId')]
    #[SuccessResponse(
        description: 'export_download_xlsx_available_success_response',
        content: new JsonContent(ref: DownloadAvailability::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function availableXlsx(int $jobRunId): JsonResponse
    {
        $available = $this->downloadService->isResourceAvailableByJobRunId(
            $jobRunId,
            ExportFile::XLSX_FILE_NAME->value,
            ExportFile::XLSX_FOLDER_NAME->value,
        );

        return $this->jsonResponse(
            new DownloadAvailability($available),
            headers: [HttpResponseHeaders::HEADER_CACHE_CONTROL->value => 'no-store'],
        );
    }
}
