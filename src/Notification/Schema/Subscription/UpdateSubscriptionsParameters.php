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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bulk by design: the preferences screen saves every row in one request, matching a single
 * save action in the UI.
 */
#[Schema(
    schema: 'NotificationUpdateSubscriptionsParameters',
    title: 'Notification Update Subscriptions Parameters',
    required: ['items'],
    type: 'object'
)]
final readonly class UpdateSubscriptionsParameters
{
    /**
     * @param UpdateSubscriptionItem[] $items
     */
    public function __construct(
        #[Assert\Valid]
        #[Property(
            description: 'preferences to store, one entry per notification type',
            type: 'array',
            items: new Items(ref: UpdateSubscriptionItem::class)
        )]
        private array $items = [],
    ) {
    }

    /**
     * @return UpdateSubscriptionItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
