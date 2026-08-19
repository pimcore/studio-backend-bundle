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

use Pimcore\Bundle\StudioBackendBundle\Entity\Notification\NotificationSubscription;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;

/**
 * @internal
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Stored rows for a user, keyed by type id; types the user never touched are absent.
     *
     * @return array<string, NotificationSubscription>
     *
     * @throws DatabaseException
     */
    public function getByUser(int $userId): array;

    /**
     * @throws DatabaseException
     */
    public function getByUserAndType(int $userId, string $typeId): ?NotificationSubscription;

    /**
     * @param array<string, array{subscribed: bool, channels: string[]}> $preferences keyed by type id
     *
     * @throws DatabaseException
     */
    public function save(int $userId, array $preferences): void;
}
