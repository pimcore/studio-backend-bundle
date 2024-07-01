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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\TreeNode;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserListParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;

/**
 * @internal
 */
interface UserServiceInterface
{
    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    public function resetPassword(ResetPassword $resetPassword): void;

    public function getUserTreeListing(UserListParameter $userListParameter): Collection;

    /**
     * @throws NotFoundException|ForbiddenException|DatabaseException
     */
    public function deleteUser(int $userId): void;

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function createUser(CreateParameter $createParameter): TreeNode;

    /**
     * @throws NotFoundException|DatabaseException
     */
    public function getUserById(int $userId): UserSchema;
}
