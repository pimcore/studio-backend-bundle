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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Controller;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SecurityServiceInterface $securityService,
        private readonly VersionRepositoryInterface $repository
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException|NotFoundException|UserNotFoundException
     */
    #[Route('/versions/{id}', name: 'pimcore_studio_api_delete_version', methods: ['DELETE'])]
    //#[IsGranted('STUDIO_API')]
    #[Delete(
        path: self::API_PATH . '/versions/{id}',
        operationId: 'deleteVersion',
        description: 'Delete version based on the version ID',
        summary: 'Delete version',
        tags: [Tags::Versions->name]
    )]
    #[IdParameter(type: 'version')]
    #[SuccessResponse(
        description: 'Successfully deleted version',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteVersion(int $id): Response
    {
        $user = $this->securityService->getCurrentUser();

        $version = $this->repository->getVersionById($id);

        $this->securityService->hasElementPermission(
            $version->getData(),
            $user,
            ElementPermissions::VERSIONS_PERMISSION
        );

        $version->delete();

        return new Response();
    }
}
