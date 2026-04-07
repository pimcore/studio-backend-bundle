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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid\Configuration;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\GridConfigurationIdParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter as IdParameterPath;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter as IdParameterQuery;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetConfigurationController extends AbstractApiController
{
    private const string ROUTE = '/data-object/grid/configuration/{folderId}/{classId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ConfigurationServiceInterface $gridConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_get_data_object_grid_configuration',
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'data_object_get_grid_configuration',
        description: 'data_object_get_grid_configuration_description',
        summary: 'data_object_get_grid_configuration_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[IdParameterPath(name: 'folderId')]
    #[StringParameter(
        name: 'classId',
        example: 'EV',
        description: 'Class Id of the data object',
    )]
    #[IdParameterQuery(description: 'Configuration ID', namePrefix: 'configuration', required: false)]
    #[SuccessResponse(
        description: 'data_object_get_grid_configuration_success_response',
        content: new JsonContent(ref: DetailedConfiguration::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getDataObjectGridConfiguration(
        int $folderId,
        string $classId,
        #[MapQueryString] GridConfigurationIdParameter $configurationId = new GridConfigurationIdParameter()
    ): JsonResponse {
        $configuration = $this->gridConfigurationService->getDataObjectGridConfiguration(
            $configurationId->getConfigurationId(),
            $folderId,
            $classId
        );

        return $this->jsonResponse($configuration);
    }
}
