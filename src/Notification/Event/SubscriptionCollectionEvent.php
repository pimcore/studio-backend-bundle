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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscriptionCollection;

final class SubscriptionCollectionEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.notification.subscription.collection';

    public function __construct(
        private readonly SubscriptionCollection $subscriptionCollection
    ) {
        parent::__construct($this->subscriptionCollection);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getSubscriptionCollection(): SubscriptionCollection
    {
        return $this->subscriptionCollection;
    }
}
