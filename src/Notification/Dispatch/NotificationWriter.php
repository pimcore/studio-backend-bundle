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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\Notification;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final readonly class NotificationWriter implements NotificationWriterInterface
{
    public function write(
        DispatchableNotification $notification,
        UserInterface $recipient,
        ?UserInterface $sender,
    ): Notification {
        try {
            $stored = new Notification();
            /** @var User $recipient */
            $stored->setRecipient($recipient);
            /** @var User|null $sender */
            $stored->setSender($sender);
            $stored->setType($notification->getTypeId());
            $stored->setTitle($notification->getTitle());
            $stored->setMessage($notification->getMessage());
            $stored->setLinkedElement($notification->getLinkedElement());
            $stored->setPayload(json_encode($notification->getPayload(), JSON_THROW_ON_ERROR));
            $stored->setIsStudio(true);
            $stored->save();

            return $stored;
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Failed to write notification: %s', $e->getMessage()),
                $e
            );
        }
    }
}
