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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Export\Attribute\Request\ExportDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExecutionEngine\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFormat;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ElementController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ExportServiceInterface $exportService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/export/xlsx', name: 'pimcore_studio_api_export_xlsx', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/export/xlsx',
        operationId: 'export_xlsx',
        description: 'export_xlsx_description',
        summary: 'export_xlsx_summary',
        tags: [Tags::Export->name]
    )]
    #[ExportDataRequestBody(addDelimiter: false)]
    #[CreatedResponse(
        description: 'export_xlsx_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function exportXlsxElements(
        #[MapRequestPayload] ExportParameter $exportParameter
    ): Response {
        return $this->jsonResponse(
            [
                'jobRunId' => $this->exportService->generateExportFileForElements(
                    $exportParameter,
                    ExportFormat::XLSX->value
                ),
            ],
            HttpResponseCodes::CREATED->value
        );
    }
}
