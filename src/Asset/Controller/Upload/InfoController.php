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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Upload;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\OpenApi\Attribute\Parameter\Query\NameParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\BoolJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class InfoController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly UploadServiceInterface $uploadService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route('/assets/exists/{parentId}', name: 'pimcore_studio_api_asset_upload_info', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/exists/{parentId}',
        operationId: 'asset_upload_info',
        description: 'asset_upload_info_description',
        summary: 'asset_upload_info_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET, name: 'parentId')]
    #[NameParameter(name: 'fileName', description: 'Name of the file to upload', example: 'file.jpg')]
    #[SuccessResponse(
        description: 'asset_upload_info_success_response',
        content: new BoolJson(name: 'exists', description: 'True if asset exists, false otherwise')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getAssetExists(int $parentId, #[MapQueryParameter] string $fileName): JsonResponse
    {

        return $this->jsonResponse(
            [
                'exists' => $this->uploadService->fileExists(
                    $parentId,
                    $fileName,
                    $this->securityService->getCurrentUser()
                ),
            ]
        );
    }
}
