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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * Everything the preferences screen needs in one response: the column set and the rows, merged
 * and narrowed for the calling user. Not readonly — the pre-response event mutates it.
 */
#[Schema(
    schema: 'NotificationSubscriptionCollection',
    title: 'Notification Subscription Collection',
    required: ['availableChannels', 'items'],
    type: 'object'
)]
class SubscriptionCollection implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param AvailableChannel[] $availableChannels
     * @param SubscribableType[] $items
     */
    public function __construct(
        /**
         * Drives the column set. When no type in the installation offers a transport channel
         * this holds the pop-up alone, and the screen renders no channel columns rather than
         * columns of dead switches.
         */
        #[Property(
            description: 'channels offerable anywhere in this installation',
            type: 'array',
            items: new Items(ref: AvailableChannel::class)
        )]
        private readonly array $availableChannels,
        #[Property(
            description: 'subscribable types with the caller\'s effective preferences',
            type: 'array',
            items: new Items(ref: SubscribableType::class)
        )]
        private readonly array $items,
    ) {
    }

    /**
     * @return AvailableChannel[]
     */
    public function getAvailableChannels(): array
    {
        return $this->availableChannels;
    }

    /**
     * @return SubscribableType[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
