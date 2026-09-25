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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\SubscriptionCollection;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Subscription\UpdateSubscriptionsParameters;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface SubscriptionServiceInterface
{
    /**
     * @throws DatabaseException
     */
    public function getSubscriptions(UserInterface $user): SubscriptionCollection;

    /**
     * Stores the caller's preferences and returns the resulting state, so the client can
     * re-seed from the server rather than trusting its own optimistic view.
     *
     * A channel the installation no longer offers, or that the type cannot use, is dropped from
     * the stored set rather than rejected. The returned state is what was actually stored.
     *
     * @throws DatabaseException
     * @throws InvalidArgumentException on an unknown type, or an attempt to unsubscribe from a
     *                                  type whose subscription is locked
     */
    public function updateSubscriptions(
        UserInterface $user,
        UpdateSubscriptionsParameters $parameters
    ): SubscriptionCollection;
}
