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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
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
final class GetAvailableColumnsController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|SearchException
     */
    #[Route(
        '/data-object/grid/available-columns/{classId}/{folderId}',
        name: 'pimcore_studio_api_get_data_objects_grid_available_columns',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/data-object/grid/available-columns/{classId}/folderId',
        operationId: 'data_object_get_available_grid_columns',
        description: 'data_object_get_available_grid_columns_description',
        summary: 'data_object_get_available_grid_columns_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[SuccessResponse(
        description: 'data_object_get_available_grid_columns_success_response',
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'columns',
                    type: 'array',
                    items: new Items(ref: ColumnConfiguration::class),
                )],
        )
    )]
    #[StringParameter(
        name: 'classId',
        example: 'MyClass',
        description: 'Identifies the class name for which the columns should be retrieved.',
    )]
    #[IdParameter(
        name: 'folderId',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getDataObjectAvailableGridColumns(string $classId, int $folderId): JsonResponse
    {
        $columns = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration($classId, $folderId);

        return $this->jsonResponse([
            'columns' => $columns,
        ]);
    }
}
