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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
final readonly class ImageService implements ImageServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SecurityServiceInterface $securityService,
        private AssetResolverInterface $assetResolver
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function uploadUserImage(UploadedFile $file, int $userId): void
    {
        $user = $this->userRepository->getUserById($userId);
        $currentUser = $this->securityService->getCurrentUser();

        if ($currentUser->isAdmin()) {
            $this->setUserImage($user, $file);

            return;
        }

        $this->validateUserAccess($user, $currentUser);

        $this->setUserImage($user, $file);
    }

    /**
     * {@inheritdoc}
     */
    public function getImageFromUserAsStreamedResponse(int $userId): StreamedResponse
    {
        $user = $this->userRepository->getUserById($userId);
        $currentUser = $this->securityService->getCurrentUser();

        if ($currentUser->isAdmin()) {
            return $this->streamUserImage($user);
        }

        if ($user->getId() !== $currentUser->getId()) {
            throw new ForbiddenException('Only admin users are allowed to view other users');
        }

        return $this->streamUserImage($user);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUserImage(int $userId): void
    {
        $user = $this->userRepository->getUserById($userId);
        $currentUser = $this->securityService->getCurrentUser();

        if ($currentUser->isAdmin()) {
            $user->setImage(null);

            return;
        }

        $this->validateUserAccess($user, $currentUser);

        $user->setImage(null);
    }

    private function validateUserAccess(UserInterface $user, UserInterface $currentUser): void
    {
        if ($user->isAdmin()) {
            throw new ForbiddenException('Only admin users are allowed to modify admin users');
        }

        if ($user->getId() !== $currentUser->getId()) {
            throw new ForbiddenException('Only admin users are allowed to modify users other than themselves');
        }
    }

    private function setUserImage(UserInterface $user, UploadedFile $file): void
    {
        $fileType = $this->assetResolver->getTypeFromMimeMapping($file->getMimeType(), $file->getFilename());

        if ($fileType !== 'image') {
            throw new ForbiddenException('Only images are allowed');
        }

        $user->setImage($file->getPathname());
    }

    private function streamUserImage(UserInterface $user): StreamedResponse
    {
        $stream = $user->getImage();

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'image/png',
        ]);
    }
}
