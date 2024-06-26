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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Attributes\Request\FolderDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\FolderData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementFolderServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementPublishingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\Finder\Exception\AccessDeniedException;
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
    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementFolderServiceInterface $elementFolderService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws ElementPublishingFailedException
     * @throws InvalidElementTypeException
     * @throws UserNotFoundException
     */
    #[Route(
        '/elements/{elementType}/folder/{parentId}',
        name: 'pimcore_studio_api_elements_create_folder',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Post(
        path: self::API_PATH . '/elements/{elementType}/folder/{parentId}',
        operationId: 'createFolder',
        description: 'Create folder by parent id and element type path parameter and folder name body parameter',
        summary: 'Create element folder',
        tags: [Tags::Elements->name]
    )]
    #[IdParameter(name: 'parentId')]
    #[ElementTypeParameter]
    #[FolderDataRequestBody]
    #[SuccessResponse(
        description: 'Successfully created folder',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function createFolder(
        int $parentId,
        string $elementType,
        #[MapRequestPayload] FolderData $folderData
    ): Response {
        $parameters = new ElementParameters($elementType, $parentId);
        $this->elementFolderService->createFolderByType(
            $parameters->getId(),
            $parameters->getType(),
            $folderData->getFolderName(),
            $this->securityService->getCurrentUser()
        );

        return new Response();
    }
}
