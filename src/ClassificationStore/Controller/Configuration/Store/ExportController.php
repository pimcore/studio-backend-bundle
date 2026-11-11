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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function sprintf;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/stores/{id}/export';

    private const string FILE_NAME = 'classification-store-%d-export.json';

    public function __construct(
        SerializerInterface $serializer,
        private readonly StoreTransferServiceInterface $storeTransferService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ConflictException
     * @throws DatabaseException
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_store_export', methods: ['GET'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_store_export',
        description: 'classification_store_configuration_store_export_description',
        summary: 'classification_store_configuration_store_export_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[IdParameter(type: 'store configuration')]
    #[SuccessResponse(
        description: 'classification_store_configuration_store_export_success_response',
        content: [new MediaType(MimeTypes::JSON->value)],
        headers: [new ContentDisposition(fileName: 'classification-store-1-export.json')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::CONFLICT,
    ])]
    public function exportStore(int $id): StreamedResponse
    {
        // a missing store is a 404 and one without identifiers a 409, as both are resolved here; a relation
        // found unresolvable while streaming can only truncate the download
        $artifact = $this->storeTransferService->exportStore($id);

        return new StreamedResponse(
            static function () use ($artifact): void {
                foreach ($artifact as $piece) {
                    echo $piece;
                }
            },
            HttpResponseCodes::SUCCESS->value,
            [
                HttpResponseHeaders::HEADER_CONTENT_TYPE->value => MimeTypes::JSON->value,
                HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value => sprintf(
                    '%s; filename="%s"',
                    HttpResponseHeaders::ATTACHMENT_TYPE->value,
                    sprintf(self::FILE_NAME, $id)
                ),
            ]
        );
    }
}
