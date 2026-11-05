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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\EventSubscriber;

use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\AccessRevocationServiceInterface;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model\User\ServiceUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Revokes a service user's credentials and MCP server grants before it is deleted, whichever
 * path deletes it. A failure aborts the deletion, so nothing is left behind for a later user
 * with the same name.
 *
 * @internal
 */
final readonly class ServiceUserDeletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AccessRevocationServiceInterface $accessRevocationService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserRoleEvents::PRE_DELETE => 'onPreDelete',
        ];
    }

    public function onPreDelete(UserRoleEvent $event): void
    {
        $user = $event->getUserRole();
        if (!$user instanceof ServiceUser) {
            return;
        }

        $this->accessRevocationService->revokeAll($user);
    }
}
