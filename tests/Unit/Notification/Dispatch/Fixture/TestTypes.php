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

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationTypeProviderInterface;

/**
 * Builds types as another bundle would contribute them.
 */
final class TestTypes
{
    /**
     * @param string[] $defaultChannels
     */
    public static function type(
        string $typeId,
        bool $allowsExternalDelivery = false,
        array $defaultChannels = ['popup'],
        bool $subscribedByDefault = true,
        bool $subscriptionLocked = false,
        int $sortOrder = 100,
        string $group = 'test',
    ): NotificationType {
        return new NotificationType(
            typeId: $typeId,
            translationKey: $typeId . '.label',
            descriptionKey: $typeId . '.description',
            group: $group,
            sortOrder: $sortOrder,
            allowsExternalDelivery: $allowsExternalDelivery,
            defaultChannels: $defaultChannels,
            subscribedByDefault: $subscribedByDefault,
            subscriptionLocked: $subscriptionLocked,
        );
    }

    public static function provider(NotificationType ...$types): NotificationTypeProviderInterface
    {
        return new class($types) implements NotificationTypeProviderInterface {
            /**
             * @param NotificationType[] $types
             */
            public function __construct(private readonly array $types)
            {
            }

            public function getTypes(): array
            {
                return $this->types;
            }
        };
    }
}
