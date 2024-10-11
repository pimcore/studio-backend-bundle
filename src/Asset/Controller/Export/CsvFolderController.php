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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Export;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\CsvExportFolderRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CsvFolderController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/assets/export/csv/folder', name: 'pimcore_studio_api_asset_export_csv_folder', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::API_PATH . '/assets/export/csv/folder',
        operationId: 'asset_export_csv_folder',
        description: 'asset_export_csv_folder_description',
        summary: 'asset_export_csv_folder_summary',
        tags: [Tags::Assets->name]
    )]
    #[CsvExportFolderRequestBody]
    #[CreatedResponse(
        description: 'asset_export_csv_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function assetExportCsvFolder(
        #[MapRequestPayload] ExportFolderParameter $exportFolderParameter
    ): Response {
        return $this->jsonResponse(
            ['jobRunId' => $this->csvService->generateCsvFileForFolders($exportFolderParameter)],
            HttpResponseCodes::CREATED->value
        );
    }
}
