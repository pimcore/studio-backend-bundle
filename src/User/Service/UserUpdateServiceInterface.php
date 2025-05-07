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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UpdateUserProfile;
use Pimcore\Model\User;

/**
 * @internal
 */
interface UserUpdateServiceInterface
{
    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException|ParseException
     */
    public function updateUserById(UpdateUserParameter $updateUserParameter, int $userId): void;

    /**
     * @throws DatabaseException|ParseException
     */
    public function updateUserProfile(User $user, UpdateUserProfile $params): void;

    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException
     */
    public function updatePasswordById(UpdatePasswordParameter $updateParameter, int $userId): void;

    /**
     * @throws ForbiddenException|NotFoundException|NotWriteableException|UserNotFoundException
     */
    public function updateActivePerspective(string $perspectiveId): void;
}
