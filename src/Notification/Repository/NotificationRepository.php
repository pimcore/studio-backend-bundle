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

use Pimcore\Bundle\StaticResolverBundle\Models\Notification\NotificationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\FilterType;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Listing;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter,
        private NotificationResolverInterface $notificationResolver
    ) {

    }

    public function getListingForCurrentUser(
        UserInterface $user,
        FilterParameter $parameters = new FilterParameter()
    ): Listing {
        $listing = $this->getListing($parameters);
        $filterParameters = new FilterParameter(
            columnFilters: [
                [
                    'key' => 'recipient',
                    'type' => FilterType::EQUALS->value,
                    'filterValue' => $user->getId(),
                ],
            ],
            sortFilter: $parameters->getSortFilter()
        );
        $this->listingFilter->applyFilters($filterParameters, $listing);

        return $listing;
    }

    /**
     * @throws NotFoundException
     */
    public function getNotificationById(int $id): Notification
    {
        $notification = $this->notificationResolver->getById($id);
        if ($notification === null) {
            throw new NotFoundException('notification', $id);
        }

        return $notification;
    }

    public function getListing(
        FilterParameter $parameters
    ): Listing {

        $listing = new Listing();
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }

    public function getUnreadCountByUser(UserInterface $user): int
    {
        $listing = new Listing();
        $listing->setCondition('recipient = ? AND `read` = 0', [$user->getId()]);

        return $listing->count();
    }
}
