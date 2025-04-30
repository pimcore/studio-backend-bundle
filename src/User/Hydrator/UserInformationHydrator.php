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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class UserInformationHydrator implements UserInformationHydratorInterface
{
    public function __construct(private UserPerspectiveServiceInterface $userPerspectiveService)
    {
    }

    public function hydrate(UserInterface $user): UserInformation
    {
        return new UserInformation(
            $user->getId(),
            $user->getUsername(),
            $user->getPermissions(),
            $user->isAdmin(),
            $user->getClasses(),
            $user->getDocTypes(),
            $user->getLanguage(),
            $this->userPerspectiveService->getActivePerspective($user),
            $this->userPerspectiveService->getAllowedPerspectives($user)
        );
    }
}
