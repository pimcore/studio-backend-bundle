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

use League\Flysystem\FilesystemException;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\AddAssetRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AddController extends AbstractApiController
{
    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly UploadServiceInterface $uploadService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws AccessDeniedException
     * @throws DatabaseException
     * @throws EnvironmentException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     * @throws FilesystemException
     */
    #[Route('/assets/add/{parentId}', name: 'pimcore_studio_api_assets_add', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . '/assets/add/{parentId}',
        operationId: 'asset_add',
        description: 'asset_add_description',
        summary: 'asset_add_summary',
        tags: [Tags::Assets->value]
    )]
    #[SuccessResponse(
        description: 'asset_add_success_response',
        content: new IdJson('ID of created asset')
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET, name: 'parentId')]
    #[AddAssetRequestBody(
        [
            new Property(
                property: 'file',
                description: 'File to upload',
                type: 'string',
                format: 'binary'
            ),
        ],
        ['file']
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function addAsset(
        int $parentId,
        // TODO: Symfony 7.1 change to https://symfony.com/blog/new-in-symfony-7-1-mapuploadedfile-attribute
        Request $request
    ): JsonResponse {

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new EnvironmentException('Invalid file found in the request');
        }

        return $this->jsonResponse(
            [
                'id' => $this->uploadService->uploadAsset(
                    $parentId,
                    $file->getClientOriginalName(),
                    $file->getRealPath(),
                    $this->securityService->getCurrentUser()
                ),
            ]
        );
    }
}
