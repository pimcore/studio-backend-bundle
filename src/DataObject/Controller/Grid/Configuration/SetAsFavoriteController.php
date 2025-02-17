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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid\Configuration;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\UpdateConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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
final class SetAsFavoriteController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly UpdateConfigurationServiceInterface $updateConfigurationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|ForbiddenException|InvalidArgumentException
     */
    #[Route(
        '/data-object/grid/configuration/set-as-favorite/{configurationId}/{folderId}',
        name: 'pimcore_studio_api_data_object_set_grid_configuration_as_favorite',
        methods: ['POST'],
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/data-object/grid/configuration/set-as-favorite/{configurationId}/{folderId}',
        operationId: 'data_object_set_grid_configuration_as_favorite',
        description: 'data_object_set_grid_configuration_as_favorite_description',
        summary: 'data_object_set_grid_configuration_as_favorite_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[IdParameter(
        type: 'configurationId',
        name: 'configurationId'
    )]
    #[IdParameter(
        type: 'folderId',
        name: 'folderId'
    )]
    #[SuccessResponse(
        description: 'data_object_set_grid_configuration_as_favorite_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function setDataObjectGridConfigurationAsFavorite(
        int $configurationId,
        int $folderId
    ): Response {
        $this->updateConfigurationService->setDataObjectGridConfigurationAsFavorite($configurationId, $folderId);

        return new Response();
    }
}
