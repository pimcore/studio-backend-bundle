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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Registry;

use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidNotificationChannelException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function sprintf;

/**
 * @internal
 */
final readonly class ChannelRegistry implements ChannelRegistryInterface
{
    /**
     * @var array<string, ChannelInterface>
     */
    private array $channels;

    /**
     * @param iterable<ChannelInterface> $taggedChannels
     * @param array<string, array{enabled: bool}> $channelConfig
     *
     * @throws InvalidNotificationChannelException
     */
    public function __construct(
        #[AutowireIterator(ChannelInterface::TAG)]
        iterable $taggedChannels,
        array $channelConfig = [],
    ) {
        $this->channels = $this->collect($taggedChannels, $channelConfig);
    }

    public function getEnabledChannels(): array
    {
        return array_values($this->channels);
    }

    public function getEnabledChannel(string $name): ?ChannelInterface
    {
        return $this->channels[$name] ?? null;
    }

    public function getAvailableChannelIds(): array
    {
        return [self::POPUP_CHANNEL, ...array_keys($this->channels)];
    }

    public function getSupportedChannelIds(NotificationTypeDescriptorInterface $descriptor): array
    {
        if (!$descriptor->allowsExternalDelivery()) {
            return [self::POPUP_CHANNEL];
        }

        return $this->getAvailableChannelIds();
    }

    /**
     * A channel the administrator has switched off is dropped here rather than flagged, so it
     * never reaches the API. The preferences screen then omits the column entirely instead of
     * rendering switches that could not do anything.
     *
     * @param iterable<ChannelInterface> $taggedChannels
     * @param array<string, array{enabled: bool}> $channelConfig
     *
     * @return array<string, ChannelInterface>
     *
     * @throws InvalidNotificationChannelException
     */
    private function collect(iterable $taggedChannels, array $channelConfig): array
    {
        $channels = [];
        foreach ($taggedChannels as $channel) {
            $name = $channel->getName();

            if ($name === self::POPUP_CHANNEL) {
                throw new InvalidNotificationChannelException(
                    sprintf(
                        'Channel name "%s" is reserved for the in-app pop-up preference and ' .
                        'cannot be used by a transport channel.',
                        self::POPUP_CHANNEL
                    )
                );
            }

            if (isset($channels[$name])) {
                throw new InvalidNotificationChannelException(
                    sprintf('Notification channel "%s" is registered more than once.', $name)
                );
            }

            if (($channelConfig[$name]['enabled'] ?? true) !== true) {
                continue;
            }

            $channels[$name] = $channel;
        }

        uasort(
            $channels,
            static fn (ChannelInterface $a, ChannelInterface $b): int
                => [$a->getSortOrder(), $a->getName()] <=> [$b->getSortOrder(), $b->getName()]
        );

        return $channels;
    }
}
