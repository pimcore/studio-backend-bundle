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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface UpdateServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function updatePermissions(
        array $permissionsToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface;

    /**
     * @throws NotFoundException
     */
    public function updateRoles(array $rolesToSet, UserInterface $user): UserInterface;

    /**
     * @throws NotFoundException
     */
    public function updateClasses(
        array $classesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface;

    /**
     * @param UserWorkspace[] $assetWorkspacesToSet
     *
     * @throws ParseException
     */
    public function updateAssetWorkspaces(
        array $assetWorkspacesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface;

    /**
     * @param UserWorkspace[] $objectWorkspacesToSet
     *
     * @throws ParseException
     */
    public function updateDataObjectWorkspaces(
        array $objectWorkspacesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface;

    /**
     * @param UserWorkspace[] $documentWorkspacesToSet
     *
     * @throws ParseException
     */
    public function updateDocumentWorkspaces(
        array $documentWorkspacesToSet,
        UserInterface|UserRoleInterface $user
    ): UserInterface|UserRoleInterface;
}
