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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Controller\Export\Csv;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Service\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IntParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\SortOrderParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/custom-report/export/csv/{name}', name: 'pimcore_studio_api_custom_report_export_csv', methods: ['GET'])]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Get(
        path: self::PREFIX . '/custom-report/export/csv/{name}',
        operationId: 'custom_report_export_csv',
        description: 'custom_report_export_csv_description',
        summary: 'custom_report_export_csv_summary',
        tags: [Tags::CustomReports->value]
    )]
    #[NameParameter(
        name: 'name',
        description: 'custom_reports_export_csv_name_parameter',
        example: 'Quality_Attributes'
    )]
    #[SortOrderParameter]
    #[StringParameter(
        name: 'sortBy',
        example: '',
        description: 'custom_reports_export_csv_sort_by_parameter',
        required: false
    )]
    #[FilterParameter('chart data', example: '')]
    #[IntParameter(
        name: 'reportOffset',
        description: 'custom_reports_export_csv_report_offset_parameter',
        required: false
    )]
    #[IntParameter(
        name: 'reportLimit',
        description: 'custom_reports_export_csv_report_limit_parameter',
        required: false
    )]
    #[BoolParameter(
        name: 'includeHeaders',
        description: 'custom_reports_export_csv_include_headers_parameter',
        required: false,
        example: false
    )]
    #[CreatedResponse(
        description: 'custom_report_export_csv_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function exportCsv(
        string $name,
        #[MapQueryString] ExportParameter $exportParameter,
    ): Response {
        return $this->jsonResponse(
            $this->csvService->generateCsvFile(
                $name,
                $exportParameter
            )
        );
    }
}
