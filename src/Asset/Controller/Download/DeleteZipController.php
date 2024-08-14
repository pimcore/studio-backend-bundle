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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Download;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteZipController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly DownloadServiceInterface $downloadService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|ForbiddenException
     */
    #[Route('/assets/download/zip/{jobRunId}', name: 'pimcore_studio_api_zip_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Delete(
        path: self::API_PATH . '/assets/download/zip/{jobRunId}',
        operationId: 'asset_delete_zip',
        description: 'asset_delete_zip_description',
        summary: 'asset_delete_zip_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: 'JobRun', name: 'jobRunId')]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteAssetsZip(int $jobRunId): Response
    {
        $this->downloadService->cleanupDataByJobRunId(
            $jobRunId,
            ZipServiceInterface::DOWNLOAD_ZIP_FOLDER_NAME,
            ZipServiceInterface::DOWNLOAD_ZIP_FILE_NAME
        );

        return new Response();
    }
}
