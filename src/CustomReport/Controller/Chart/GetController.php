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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Controller\Chart;

use Exception;
use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IntParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\SortOrderParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomReportServiceInterface $customReportService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|DatabaseException
     * @throws Exception
     */
    #[Route('/custom-reports/chart/{name}',
        name: 'pimcore_studio_api_custom_reports_chart',
        methods: ['GET'])
    ]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Get(
        path: self::PREFIX . '/custom-reports/chart/{name}',
        operationId: 'custom_reports_chart',
        summary: 'custom_reports_chart_summary',
        tags: [Tags::CustomReports->value]
    )]
    #[NameParameter(
        name: 'name',
        description: 'custom_reports_chart_name_parameter',
        example: 'Quality_Attributes'
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[SortOrderParameter]
    #[StringParameter(
        name: 'sortBy',
        example: '',
        description: 'custom_reports_chart_sort_by_parameter',
        required: false
    )]
    #[FilterParameter('chart data', example: '')]
    #[IntParameter(
        name: 'reportOffset',
        description: 'custom_reports_chart_report_offset_parameter',
        required: false
    )]
    #[IntParameter(
        name: 'reportLimit',
        description: 'custom_reports_chart_report_limit_parameter',
        required: false
    )]
    #[SuccessResponse(
        description: 'custom_reports_chart_success_response',
        content: new ItemsJson(CustomReportChartData::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getChartData(
        string $name,
        #[MapQueryString] ExportParameter $chartDataParameter
    ): JsonResponse {
        return $this->jsonResponse(
            $this->customReportService->getChartData($name, $chartDataParameter)
        );
    }
}
