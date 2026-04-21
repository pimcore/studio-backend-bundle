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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Controller\Csv;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Export\Attribute\Request\ExportFolderDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExecutionEngine\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Constant\ExportFormat;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
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
    private const string ROUTE = '/export/csv/folder/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ExportServiceInterface $exportService
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_export_csv_folder', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'export_csv_folder',
        description: 'export_csv_folder_description',
        summary: 'export_csv_folder_summary',
        tags: [Tags::Export->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_FOLDER, name: 'id')]
    #[ExportFolderDataRequestBody]
    #[CreatedResponse(
        description: 'export_csv_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function exportCsvFolder(
        int $id,
        #[MapRequestPayload] ExportFolderParameter $exportParameter
    ): Response {
        return $this->jsonResponse(
            [
                'jobRunId' => $this->exportService->generateExportFileForFolders(
                    $id,
                    $exportParameter,
                    ExportFormat::CSV->value
                ),
            ],
            HttpResponseCodes::CREATED->value
        );
    }
}
