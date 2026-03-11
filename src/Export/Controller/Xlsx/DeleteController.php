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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFile;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly DownloadServiceInterface $downloadService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|NotFoundException
     */
    #[Route('/export/download/xlsx/{jobRunId}', name: 'pimcore_studio_api_export_delete_xlsx', methods: ['DELETE'])]
    #[Delete(
        path: self::PREFIX . '/export/download/xlsx/{jobRunId}',
        operationId: 'export_delete_xlsx',
        description: 'export_delete_xlsx_description',
        summary: 'export_delete_xlsx_summary',
        tags: [Tags::Export->value]
    )]
    #[IdParameter(type: 'JobRun', name: 'jobRunId')]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteXlsx(int $jobRunId): Response
    {
        $this->downloadService->cleanupDataByJobRunId(
            $jobRunId,
            ExportFile::XLSX_FOLDER_NAME->value,
            ExportFile::XLSX_FILE_NAME->value,
        );

        return new Response();
    }
}
