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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\UserInformation;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserInformationEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserInformationHydratorInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class UserInformationService implements UserInformationServiceInterface
{
    public function __construct(
        private UserInformationHydratorInterface $userInformationHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getUserInformation(UserInterface $user): UserInformation
    {
        $userInformation = $this->userInformationHydrator->hydrate($user);

        $this->eventDispatcher->dispatch(
            new UserInformationEvent($userInformation),
            UserInformationEvent::EVENT_NAME
        );

        return $userInformation;
    }
}
