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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;

/**
 * Stands in for a transport channel contributed by another bundle. Records what it was asked
 * to deliver, and can be told to fail so the dispatcher's isolation can be exercised.
 */
final class TestChannel implements ChannelInterface
{
    /**
     * @var array<int, array{notification: Notification, recipient: UserInterface}>
     */
    public array $sent = [];

    public function __construct(
        private readonly string $name = 'test',
        private readonly int $sortOrder = 100,
        private readonly bool $throwOnSend = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function send(Notification $notification, UserInterface $recipient): void
    {
        if ($this->throwOnSend) {
            throw new Exception('Channel is broken');
        }

        $this->sent[] = ['notification' => $notification, 'recipient' => $recipient];
    }
}
