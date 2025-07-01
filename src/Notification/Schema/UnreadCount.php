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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'UnreadCount',
    title: 'Unread Count',
    required: ['unreadNotificationsCount'],
    type: 'object'
)]
final readonly class UnreadCount
{
    public function __construct(
        #[Property(description: 'Count of unread notifications', type: 'integer', example: 1)]
        private int $unreadNotificationsCount,
    ) {
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->unreadNotificationsCount;
    }
}
