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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkImport\BulkImportPrepareResponse;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\MultipartFormDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PrepareImportController extends AbstractApiController
{
    private const string ROUTE = '/class/bulk-import/prepare';

    public function __construct(
        SerializerInterface $serializer,
        private readonly BulkImportServiceInterface $bulkImportService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException
     * @throws ApiInvalidArgumentException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_class_bulk_import_prepare',
        methods: ['POST'],
        priority: 10,
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_bulk_import_prepare',
        description: 'class_bulk_import_prepare_description',
        summary: 'class_bulk_import_prepare_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[MultipartFormDataRequestBody(
        [
            new Property(
                property: 'file',
                description: 'Bulk export JSON file to analyze',
                type: 'string',
                format: 'binary'
            ),
        ],
        ['file']
    )]
    #[SuccessResponse(
        description: 'class_bulk_import_prepare_success_response',
        content: new JsonContent(ref: BulkImportPrepareResponse::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function prepareImport(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new ApiInvalidArgumentException(
                'Invalid file found in the request'
            );
        }

        return $this->jsonResponse(
            $this->bulkImportService->prepareImport($file)
        );
    }
}
