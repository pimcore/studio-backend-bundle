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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Notification;

use Doctrine\ORM\Mapping as ORM;

/**
 * One row per (user, notification type); a missing row means "use the descriptor defaults".
 * Channels are a JSON set rather than columns so a new channel needs no migration.
 *
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: NotificationSubscription::TABLE_NAME)]
class NotificationSubscription
{
    public const string TABLE_NAME = 'bundle_studio_notification_subscription';

    #[ORM\Id]
    #[ORM\Column(name: 'user_id', type: 'integer', options: ['unsigned' => true])]
    private int $userId;

    #[ORM\Id]
    #[ORM\Column(name: 'type_id', type: 'string', length: 190)]
    private string $typeId;

    #[ORM\Column(name: 'subscribed', type: 'boolean')]
    private bool $subscribed;

    /**
     * Enabled channel ids. Null means "never chosen" (descriptor defaults apply); an empty
     * array is a deliberate "none" — collapsing the two would resurrect defaults.
     *
     * @var string[]|null
     */
    #[ORM\Column(name: 'channels', type: 'json', nullable: true)]
    private ?array $channels;

    /**
     * @param string[]|null $channels
     */
    public function __construct(
        int $userId,
        string $typeId,
        bool $subscribed,
        ?array $channels = null,
    ) {
        $this->userId = $userId;
        $this->typeId = $typeId;
        $this->subscribed = $subscribed;
        $this->channels = $channels;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    public function setSubscribed(bool $subscribed): void
    {
        $this->subscribed = $subscribed;
    }

    /**
     * @return string[]|null
     */
    public function getChannels(): ?array
    {
        return $this->channels;
    }

    /**
     * @param string[]|null $channels
     */
    public function setChannels(?array $channels): void
    {
        $this->channels = $channels;
    }
}
