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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Controller\Csv;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Export\Attribute\Request\CsvExportFolderRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
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
final class FolderController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CsvServiceInterface $csvService
    ) {
        parent::__construct($serializer);
    }

    #[Route('/export/csv/folder', name: 'pimcore_studio_api_export_csv_folder', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . '/export/csv/folder',
        operationId: 'export_csv_folder',
        description: 'export_csv_folder_description',
        summary: 'export_csv_folder_summary',
        tags: [Tags::Export->name]
    )]
    #[CsvExportFolderRequestBody]
    #[CreatedResponse(
        description: 'export_csv_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function exportCsvFolder(
        #[MapRequestPayload] ExportParameter $exportParameter
    ): Response {
        return $this->jsonResponse(
            ['jobRunId' => $this->csvService->generateCsvFileForFolders($exportParameter)],
            HttpResponseCodes::CREATED->value
        );
    }
}
