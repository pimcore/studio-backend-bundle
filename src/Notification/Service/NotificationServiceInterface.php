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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Notification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\NotificationListItem;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\UnreadCount;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\Notification as NotificationModel;

/**
 * @internal
 */
interface NotificationServiceInterface
{
    /**
     * @throws ForbiddenException
     * @throws UserNotFoundException
     */
    public function getNotificationById(int $id): Notification;

    /**
     * @throws UserNotFoundException
     */
    public function listNotifications(FilterParameter $parameters): Collection;

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function markNotificationAsRead(int $id): void;

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function deleteNotificationById(int $id): void;

    /**
     * @throws UserNotFoundException
     */
    public function deleteAllUserNotifications(): void;

    /**
     * @throws UserNotFoundException
     */
    public function getUnreadNotificationsCount(): UnreadCount;

    public function hydrateListItem(NotificationModel $notification): NotificationListItem;
}
