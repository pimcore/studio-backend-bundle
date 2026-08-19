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
     * Unsubscribed or unpermitted recipients are skipped; per-recipient failures are logged
     * and the fan-out continues.
     *
     * @throws NotFoundException when the type id is not registered — raised before any
     *                           recipient is processed
     */
    public function dispatch(DispatchableNotification $notification): void;
}
