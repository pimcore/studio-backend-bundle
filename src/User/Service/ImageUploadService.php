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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
final readonly class ImageUploadService implements ImageUploadServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SecurityServiceInterface $securityService,
        private AssetResolverInterface $assetResolver
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function uploadUserImage(UploadedFile $file, int $userId): void
    {
        $user = $this->userRepository->getUserById($userId);
        $currentUser = $this->securityService->getCurrentUser();

        if ($user->isAdmin() && !$currentUser->isAdmin()) {
            throw new ForbiddenException('You are not allowed to upload an image for an admin user');
        }

        $fileType = $this->assetResolver->getTypeFromMimeMapping($file->getMimeType(), $file->getFilename());

        if ($fileType !== 'image') {
            throw new ForbiddenException('Only images are allowed');
        }

        $user->setImage($file->getPathname());
    }
}
