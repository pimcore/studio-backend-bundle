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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UserPerspectiveServiceInterface
{
    /**
     * @throws NotFoundException|NotWriteableException
     *
     * @return PerspectiveConfig[]
     */
    public function getAllowedPerspectives(UserInterface $user): array;

    /**
     * @throws NotFoundException|NotWriteableException
     *
     * @return PerspectiveConfig[]
     */
    public function getConfigPerspectives(UserInterface|UserRoleInterface $user): array;

    public function getActivePerspective(UserInterface $user): string;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function updatePerspectives(
        array $perspectivesToSet,
        UserInterface|UserRoleInterface $user
    ): void;

    /**
     * @throws ForbiddenException|NotFoundException|NotWriteableException
     */
    public function updateActivePerspective(string $perspectiveId, UserInterface $user): void;

    /**
     * @throws ForbiddenException
     */
    public function validatePerspectiveAccess(UserInterface $user, string $perspectiveId): void;
}
