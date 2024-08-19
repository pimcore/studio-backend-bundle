<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Repository;

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Listing;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface NotificationRepositoryInterface
{
    public function getListingForCurrentUser(
        UserInterface $user,
        CollectionParameters $parameters
    ): Listing;

    public function getNotificationById(int $id): Notification;

    public function getListing(
        CollectionParameters $parameters
    ): Listing;
}
