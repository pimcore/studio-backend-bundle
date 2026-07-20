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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Subscription;

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry\ChannelRegistryInterface;
use function in_array;

/**
 * What a user's preferences actually amount to for one notification type, after the stored
 * row has been merged over the descriptor defaults and narrowed to what the type and the
 * installation can currently offer.
 */
final readonly class EffectiveSubscription
{
    /**
     * @param string[] $channels channel ids that are both chosen and currently offerable
     */
    public function __construct(
        private string $typeId,
        private bool $subscribed,
        private array $channels,
    ) {
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    /**
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    public function hasChannel(string $channelId): bool
    {
        return in_array($channelId, $this->channels, true);
    }

    /**
     * Whether an incoming notification of this type should raise a toast for this user. False
     * when unsubscribed, so a muted type cannot pop up on its way into the bell.
     */
    public function wantsPopup(): bool
    {
        return $this->subscribed && $this->hasChannel(ChannelRegistryInterface::POPUP_CHANNEL);
    }

    /**
     * Transport channels only — the pop-up is a presentation preference, not something to
     * deliver through.
     *
     * @return string[]
     */
    public function getTransportChannels(): array
    {
        return array_values(
            array_filter(
                $this->channels,
                static fn (string $channel): bool => $channel !== ChannelRegistryInterface::POPUP_CHANNEL
            )
        );
    }
}
