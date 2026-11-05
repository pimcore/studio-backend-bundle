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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
interface ServiceUserRepositoryInterface
{
    /**
     * @return ServiceUser[]
     */
    public function listServiceUsers(): array;

    /**
     * @throws NotFoundException when the id is unknown or belongs to a human user, a role or a folder
     */
    public function getServiceUser(int $id): ServiceUser;

    /**
     * @throws ElementExistsException when a user or service user already has the name
     * @throws DatabaseException
     */
    public function saveServiceUser(ServiceUser $serviceUser): void;

    /**
     * @throws DatabaseException
     */
    public function deleteServiceUser(ServiceUser $serviceUser): void;
}
