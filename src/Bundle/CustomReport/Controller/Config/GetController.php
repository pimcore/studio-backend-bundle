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

use Exception;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\PermissionsToCheck;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    private const string ROUTE = '/bundle/custom-reports/report/{name}';

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
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_report', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_report',
        summary: 'custom_reports_report_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[NameParameter(
        name: 'name',
        description: 'custom_reports_report_name_parameter',
        example: 'Quality_Attributes'
    )]
    #[SuccessResponse(
        description: 'custom_reports_report_success_response',
        content: new JsonContent(ref: CustomReportDetails::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getByName(string $name): JsonResponse
    {
        $this->denyAccessUnlessGranted(
            'HasOneOf',
            new PermissionsToCheck([
                CustomReportPermissions::REPORTS_CONFIG->value,
                CustomReportPermissions::REPORTS->value,
            ])
        );

        return $this->jsonResponse(
            $this->customReportService->getCustomReportDetails($name)
        );
    }
}
