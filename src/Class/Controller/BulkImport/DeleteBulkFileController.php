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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkImport;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportFileServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteBulkFileController extends AbstractApiController
{
    private const string ROUTE = '/class/bulk-import/{fileId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly BulkImportFileServiceInterface $bulkImportFileService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     * @throws EnvironmentException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_class_bulk_import_delete_file',
        methods: ['DELETE'],
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_bulk_import_delete_file',
        description: 'class_bulk_import_delete_file_description',
        summary: 'class_bulk_import_delete_file_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'fileId',
        example: '6792e2b43f0a7',
        description: 'File identifier returned by the prepare import endpoint',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_bulk_import_delete_file_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteBulkFile(string $fileId): Response
    {
        $this->bulkImportFileService->deleteBulkFile($fileId);

        return new Response();
    }
}
