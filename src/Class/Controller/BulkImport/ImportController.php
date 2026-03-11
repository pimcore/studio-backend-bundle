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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkImportParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportJobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ImportController extends AbstractApiController
{
    private const string ROUTE = '/class/bulk-import/{fileId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly BulkImportJobServiceInterface $bulkImportJobService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_class_bulk_import',
        methods: ['POST'],
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_bulk_import',
        description: 'class_bulk_import_description',
        summary: 'class_bulk_import_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'fileId',
        example: '6792e2b43f0a7',
        description: 'File identifier returned by the prepare import endpoint',
        required: true
    )]
    #[ReferenceRequestBody(BulkImportParameters::class)]
    #[CreatedResponse(
        description: 'class_bulk_import_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function importItems(
        string $fileId,
        #[MapRequestPayload] BulkImportParameters $parameters,
    ): JsonResponse {
        return $this->jsonResponse(
            ['jobRunId' => $this->bulkImportJobService->importItems($fileId, $parameters)],
            HttpResponseCodes::CREATED->value,
        );
    }
}
