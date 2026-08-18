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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\DispatchableNotification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\NotificationWriterInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;
use function in_array;

/**
 * Stands in for the bell-row write. Records who was written for, and can be told to fail for
 * specific recipients so the dispatcher's per-recipient isolation can be exercised.
 *
 * Returning a bare Notification is safe: constructing the model touches no database, only
 * save() does — which is exactly why the real writer is a separate collaborator.
 */
final class TestNotificationWriter implements NotificationWriterInterface
{
    /**
     * @var array<int, array{notification: DispatchableNotification, recipient: UserInterface, sender: ?UserInterface}>
     */
    public array $written = [];

    /**
     * @param int[] $failForRecipientIds
     */
    public function __construct(private readonly array $failForRecipientIds = [])
    {
    }

    public function write(
        DispatchableNotification $notification,
        UserInterface $recipient,
        ?UserInterface $sender,
    ): Notification {
        if (in_array($recipient->getId(), $this->failForRecipientIds, true)) {
            throw new DatabaseException('Simulated write failure');
        }

        $this->written[] = [
            'notification' => $notification,
            'recipient' => $recipient,
            'sender' => $sender,
        ];

        return new Notification();
    }

    /**
     * @return int[]
     */
    public function writtenRecipientIds(): array
    {
        return array_map(
            static fn (array $entry): int => $entry['recipient']->getId(),
            $this->written
        );
    }
}
