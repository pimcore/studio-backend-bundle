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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Controller\Download;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Export\Csv\CsvExportService;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DownloadCsvController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly DownloadServiceInterface $downloadService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|ForbiddenException|NotFoundException|StreamResourceNotFoundException
     */
    #[Route('/export/download/csv/{jobRunId}', name: 'pimcore_studio_api_export_download_csv', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . '/export/download/csv/{jobRunId}',
        operationId: 'export_download_csv',
        description: 'export_download_csv_description',
        summary: 'export_download_csv_summary',
        tags: [Tags::Export->value]
    )]
    #[IdParameter(type: 'JobRun', name: 'jobRunId')]
    #[SuccessResponse(
        description: 'export_download_csv_success_response',
        content: [new MediaType('application/csv')],
        headers: [new ContentDisposition()]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function downloadCsv(int $jobRunId): StreamedResponse
    {
        return $this->downloadService->downloadResourceByJobRunId(
            $jobRunId,
            CsvExportService::CSV_FILE_NAME,
            CsvExportService::CSV_FOLDER_NAME,
            MimeTypes::CSV->value,
            'export.csv'
        );
    }
}
