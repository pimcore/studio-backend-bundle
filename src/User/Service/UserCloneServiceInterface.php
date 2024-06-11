<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserTreeNode;

/**
 * @internal
 */
interface UserCloneServiceInterface
{
    /**
     * Clone a user with the given id and assign the given name to the new user.
     *
     * @throws DatabaseException|NotFoundException
     */
    public function cloneUser(int $userId, string $userName): UserTreeNode;
}