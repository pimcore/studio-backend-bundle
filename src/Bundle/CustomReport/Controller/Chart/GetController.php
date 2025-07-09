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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Controller\Chart;

use Exception;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Request\ChartDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportChartData;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/bundle/custom-reports/chart';

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
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_chart', methods: ['POST'])]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_chart',
        description: 'custom_reports_chart_description',
        summary: 'custom_reports_chart_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[ChartDataRequestBody(
        [
            new Property(
                property: 'fields',
                description: 'Fields to be included in the chart data.' .
                ' If not provided, all fields will be included.',
                type: 'array',
                items: new Items(type: 'string', example: 'field1')
            ),
        ]
    )]
    #[SuccessResponse(
        description: 'custom_reports_chart_success_response',
        content: new CollectionJson(new GenericCollection(CustomReportChartData::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getChartData(#[MapRequestPayload] ChartDataParameter $parameters): JsonResponse
    {
        $collection = $this->customReportService->getChartData($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
