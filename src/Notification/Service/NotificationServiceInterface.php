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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface NotificationServiceInterface
{
    /**
     * @throws UserNotFoundException
     */
    public function listNotifications(CollectionParameters $parameters): Collection;

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function markNotificationAsRead(int $id): void;

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function deleteNotificationById(int $id): void;
}
