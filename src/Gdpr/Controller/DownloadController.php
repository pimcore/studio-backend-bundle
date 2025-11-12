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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DownloadController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GdprManagerServiceInterface $gdprManagerService
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/gdpr/export/download/{jobId}',
        name: 'pimcore_studio_api_gdpr_export_download',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::GDPR->value)]
    #[Get(
        path: self::PREFIX . '/gdpr/export/download/{jobId}',
        operationId: 'download_gdpr_export',
        summary: 'download_gdpr_export_summary',
        description: 'download_gdpr_export_description',
        tags: [Tags::Export->name]
    )]
    #[SuccessResponse(
        description: 'The exported file (CSV or XLSX)'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function download(int $jobId): StreamedResponse
    {
        return $this->gdprManagerService->getExportFile(
            $jobId
        );
    }
}
