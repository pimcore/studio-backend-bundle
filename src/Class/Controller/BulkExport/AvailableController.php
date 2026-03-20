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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\BulkExport;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkExport\BulkExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AvailableController extends AbstractApiController
{
    private const string ROUTE = '/class/bulk-export/available';

    public function __construct(
        SerializerInterface $serializer,
        private readonly BulkExportServiceInterface $bulkExportService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws EnvironmentException|UserNotFoundException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_class_bulk_export_available',
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_bulk_export_available',
        description: 'class_bulk_export_available_description',
        summary: 'class_bulk_export_available_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[SuccessResponse(
        description: 'class_bulk_export_available_success_response',
        content: new ItemsJson(BulkExportAvailableItem::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getAvailableItems(): JsonResponse
    {
        return $this->jsonResponse(
            ['items' => $this->bulkExportService->getAvailableItems()]
        );
    }
}
