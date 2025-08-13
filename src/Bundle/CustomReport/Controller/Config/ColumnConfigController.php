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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Controller\Config;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnInformation;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDataSourceConfig;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\ColumnServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ColumnConfigController extends AbstractApiController
{
    private const string ROUTE = '/bundle/custom-reports/column-config/{name}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ColumnServiceInterface $columnService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_column_config', methods: ['POST'])]
    #[IsGranted(CustomReportPermissions::REPORTS_CONFIG->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_column_config_list',
        description: 'custom_reports_column_config_list_description',
        summary: 'custom_reports_column_config_list_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[NameParameter(
        name: 'name',
        description: 'custom_reports_report_name_parameter',
        example: 'Quality_Attributes'
    )]
    #[ReferenceRequestBody(
        referenceClass: CustomReportDataSourceConfig::class
    )]
    #[SuccessResponse(
        description: 'custom_reports_column_config_list_success_response',
        content: new ItemsJson(CustomReportColumnInformation::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getColumnConfig(
        string $name,
        #[MapRequestPayload] CustomReportDataSourceConfig $parameters
    ): JsonResponse {

        return $this->jsonResponse(['items' => $this->columnService->getColumnConfig($name, $parameters)]);
    }
}
