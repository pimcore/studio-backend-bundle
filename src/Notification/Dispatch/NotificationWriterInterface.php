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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;

/**
 * Writes the bell entry for one recipient. Split out so the dispatcher's routing can be tested
 * without a database (Dao-proxied save() silently no-ops on a mocked model).
 *
 * @internal
 */
interface NotificationWriterInterface
{
    /**
     * @throws DatabaseException
     */
    public function write(
        DispatchableNotification $notification,
        UserInterface $recipient,
        ?UserInterface $sender,
    ): Notification;
}
