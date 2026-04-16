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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Path\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportClone;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
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
final class CloneController extends AbstractApiController
{
    private const string ROUTE = '/bundle/custom-reports/config/clone/{name}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomReportConfigServiceInterface $customReportConfigService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_config_clone', methods: ['POST'])]
    #[IsGranted(CustomReportPermissions::REPORTS_CONFIG->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_config_clone',
        description: 'custom_reports_config_clone_description',
        summary: 'custom_reports_config_clone_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[NameParameter(name: 'name', description: 'Name of the report to clone', example: 'myOriginalReport')]
    #[ReferenceRequestBody(
        referenceClass: CustomReportClone::class
    )]
    #[SuccessResponse(
        description: 'custom_reports_config_clone_success_response',
        content: new JsonContent(ref: CustomReportDetails::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function cloneCustomReport(
        string $name,
        #[MapRequestPayload] CustomReportClone $parameters
    ): JsonResponse {

        return $this->jsonResponse($this->customReportConfigService->cloneCustomReport($name, $parameters));
    }
}
