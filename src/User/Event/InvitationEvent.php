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

namespace Pimcore\Bundle\StudioBackendBundle\User\Event;

use Pimcore\Model\User;
use Symfony\Contracts\EventDispatcher\Event;

final class InvitationEvent extends Event
{
    public const string EVENT_NAME = 'pimcore.studio.user.invitation';

    protected bool $sendMail = true;

    public function __construct(
        private readonly User $user,
        private readonly string $invitationUrl
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getInvitationUrl(): string
    {
        return $this->invitationUrl;
    }

    /**
     * Determines if the invitation mail should be sent
     */
    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    /**
     * Sets flag whether to send the invitation mail or not
     */
    public function setSendMail(bool $sendMail): InvitationEvent
    {
        $this->sendMail = $sendMail;

        return $this;
    }
}
