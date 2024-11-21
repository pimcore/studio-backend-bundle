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

namespace Pimcore\Bundle\StudioBackendBundle\User\Controller;

use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\MultipartFormDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ImageUploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UploadUserImageController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly ImageUploadServiceInterface $imageUploadService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException|ParseException
     */
    #[Route('/user/upload-image/{id}', name: 'pimcore_studio_api_user_upload_image', methods: ['POST'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::PREFIX . '/user/upload-image/{id}',
        operationId: 'user_upload_image',
        summary: 'user_upload_image_summary',
        tags: [Tags::User->value]
    )]
    #[IdParameter(type: 'User')]
    #[SuccessResponse]
    #[MultipartFormDataRequestBody(
        [
            new Property(
                property: 'userImage',
                description: 'User image to upload',
                type: 'string',
                format: 'binary'
            ),
        ],
        ['userImage']
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::FORBIDDEN,
    ])]
    public function uploadUserImage(
        int $id,
        // TODO: Symfony 7.1 change to https://symfony.com/blog/new-in-symfony-7-1-mapuploadedfile-attribute
        Request $request
    ): Response {
        $file = $request->files->get('userImage');
        if (!$file instanceof UploadedFile) {
            throw new EnvironmentException('Invalid file found in the request');
        }

        $this->imageUploadService->uploadUserImage($file, $id);

        return new Response();
    }
}
