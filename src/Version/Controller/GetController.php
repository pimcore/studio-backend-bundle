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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Version\Attribute\Response\Content\OneOfVersionJson;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionDetailServiceInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SecurityServiceInterface $securityService,
        private readonly VersionDetailServiceInterface $versionDetailService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException|NotFoundException|InvalidElementTypeException|UserNotFoundException
     */
    #[Route('/versions/{id}', name: 'pimcore_studio_api_get_version', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[Get(
        path: self::PREFIX . '/versions/{id}',
        operationId: 'version_get_by_id',
        description: 'version_get_by_id_description',
        summary: 'version_get_by_id_summary',
        tags: [Tags::Versions->name]
    )]
    #[IdParameter(type: 'version')]
    #[SuccessResponse(
        description: 'version_get_by_id_success_response',
        content: new OneOfVersionJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getVersions(int $id): JsonResponse
    {
        return $this->jsonResponse(
            $this->versionDetailService->getVersionData(
                $id,
                $this->securityService->getCurrentUser()
            )
        );
    }
}
