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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
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
        FilterParameter $parameters = new FilterParameter()
    ): Listing;

    /**
     * @throws NotFoundException
     */
    public function getNotificationById(int $id): Notification;

    public function getListing(
        FilterParameter $parameters
    ): Listing;
}
