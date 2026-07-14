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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Controller\Export\Csv;

use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Request\ChartDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    private const string ROUTE = '/bundle/custom-reports/export/csv';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_report_export_csv', methods: ['Post'])]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_report_export_csv',
        description: 'custom_report_export_csv_description',
        summary: 'custom_report_export_csv_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[ChartDataRequestBody(
        additionalProperties: [
            new Property(
                property: 'includeHeaders',
                type: 'bool',
                example: false
            ),
            new Property(
                property: StepConfig::SETTINGS_DELIMITER->value,
                type: 'string',
                example: ';'
            ),
        ]
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
        #[MapRequestPayload] ExportParameter $exportParameter
    ): Response {

        return $this->jsonResponse(
            $this->csvService->generateCsvFile($exportParameter)
        );
    }
}
