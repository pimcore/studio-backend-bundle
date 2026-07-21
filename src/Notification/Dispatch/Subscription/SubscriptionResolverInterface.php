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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;

/**
 * Merges stored preferences over descriptor defaults and narrows the result to what the type
 * and the installation can currently offer. The single place that rule lives — the dispatcher,
 * the Mercure publisher and the preferences API all read through it, so they cannot drift.
 *
 * @internal
 */
interface SubscriptionResolverInterface
{
    /**
     * @throws DatabaseException
     */
    public function resolve(int $userId, NotificationTypeDescriptorInterface $descriptor): EffectiveSubscription;

    /**
     * Every registered type for one user, in registry order, with one query.
     *
     * @return array<string, EffectiveSubscription> keyed by type id
     *
     * @throws DatabaseException
     */
    public function resolveAll(int $userId): array;
}
