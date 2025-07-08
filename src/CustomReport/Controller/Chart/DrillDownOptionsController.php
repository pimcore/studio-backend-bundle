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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Controller\Chart;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Attribute\Request\DrillDownRequestBody;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\DrillDownParameter;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema\CustomReportDrillDownOption;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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
final class DrillDownOptionsController extends AbstractApiController
{
    private const string ROUTE = '/custom-reports/drill-down-options';

    public function __construct(
        private readonly CustomReportServiceInterface $service,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_drill_down_options_list', methods: ['POST'])]
    #[IsGranted(CustomReportPermissions::REPORTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_list_drill_down_options',
        description: 'custom_reports_list_drill_down_options_description',
        summary: 'custom_reports_list_drill_down_options_summary',
        tags: [Tags::CustomReports->value]
    )]
    #[DrillDownRequestBody]
    #[SuccessResponse(
        description: 'custom_reports_list_drill_down_options_success_response',
        content: new ItemsJson((CustomReportDrillDownOption::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getDrillDownOptions(
        #[MapRequestPayload] DrillDownParameter $parameters
    ): JsonResponse {

        return $this->jsonResponse(['items' => $this->service->getDrillDownOptions($parameters)]);
    }
}
