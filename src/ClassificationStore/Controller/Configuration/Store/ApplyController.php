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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Store;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\ImportReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\MultipartFormDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ApplyController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/stores/apply';

    public function __construct(
        SerializerInterface $serializer,
        private readonly StoreTransferServiceInterface $storeTransferService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ConflictException
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_store_apply', methods: ['POST'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_store_apply',
        description: 'classification_store_configuration_store_apply_description',
        summary: 'classification_store_configuration_store_apply_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[MultipartFormDataRequestBody(
        [
            new Property(
                property: 'file',
                description: 'Store configuration artifact as written by the export endpoint',
                type: 'string',
                format: 'binary'
            ),
            new Property(
                property: 'dryRun',
                description: 'Report what would be decided and write nothing',
                type: 'boolean',
                default: false
            ),
        ],
        ['file']
    )]
    #[SuccessResponse(
        description: 'classification_store_configuration_store_apply_success_response',
        content: new JsonContent(ref: ImportReport::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::CONFLICT,
    ])]
    public function applyConfiguration(
        Request $request,
        #[MapUploadedFile] UploadedFile $file,
    ): JsonResponse {
        // read off the form bag by hand: a multipart body is not a payload the mapper can bind
        return $this->jsonResponse(
            $this->storeTransferService->applyConfiguration(
                $file->getContent(),
                $request->request->getBoolean('dryRun')
            )
        );
    }
}
