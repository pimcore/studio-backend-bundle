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
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\FileUpload;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDetails;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\MultipartFormDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CustomReportPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ImportController extends AbstractApiController
{
    private const string ROUTE = '/bundle/custom-reports/config/import';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomReportConfigServiceInterface $customReportConfigService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|NotWriteableException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_custom_reports_config_import', methods: ['POST'])]
    #[IsGranted(CustomReportPermissions::REPORTS_CONFIG->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'custom_reports_config_import',
        description: 'custom_reports_config_import_description',
        summary: 'custom_reports_config_import_summary',
        tags: [Tags::BundleCustomReports->value]
    )]
    #[MultipartFormDataRequestBody(
        properties: [
            new FileUpload(description: 'Custom report JSON export file to upload'),
        ],
        required: ['file']
    )]
    #[CreatedResponse(
        description: 'custom_reports_config_import_success_response',
        content: new JsonContent(ref: CustomReportDetails::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function importCustomReport(
        #[MapUploadedFile] UploadedFile $file
    ): JsonResponse {
        return $this->jsonResponse(
            $this->customReportConfigService->importCustomReport($file->getContent()),
            HttpResponseCodes::CREATED->value
        );
    }
}
