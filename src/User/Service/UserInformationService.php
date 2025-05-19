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
