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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * Entry point for producers. Resolves each recipient's preferences, writes the bell entry for
 * those subscribed, and hands the result to every channel they have enabled.
 */
interface NotificationDispatcherInterface
{
    /**
     * Recipients who are not subscribed to the type, or who lack the notifications
     * permission, are skipped silently — that is the point of a subscription.
     *
     * Failures never propagate and recipients are independent: a broken transport, or a bell row
     * that cannot be written for one recipient, is logged and the fan-out continues. The one
     * exception is an unregistered type id, raised before any recipient is processed.
     *
     * @throws NotFoundException when the type id is not registered
     */
    public function dispatch(DispatchableNotification $notification): void;
}
