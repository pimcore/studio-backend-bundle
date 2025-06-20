<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */


namespace Pimcore\Bundle\StudioBackendBundle\Notification\Service;

use Pimcore\Bundle\StudioBackendBundle\Notification\Event\RecipientEvent;
use Pimcore\Bundle\StudioBackendBundle\Notification\Hydrator\RecipientHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Notification\Service\UserService as CoreUserService;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly CoreUserService $coreUserService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RecipientHydratorInterface $recipientHydrator,
    )
    {
    }


    public function getRecipientsForCurrentUser(): array
    {
        /* @var User $currentUser */
        $currentUser = $this->securityService->getCurrentUser();
        $users = $this->coreUserService->findAll($currentUser);

        $recipients = [];
        foreach ($users as $user) {
            $recipient = $this->recipientHydrator->hydrate($user);

            $this->eventDispatcher->dispatch(
                new RecipientEvent($recipient),
                RecipientEvent::EVENT_NAME
            );

            $recipients[] = $recipient;
        }

        return $recipients;
    }
}