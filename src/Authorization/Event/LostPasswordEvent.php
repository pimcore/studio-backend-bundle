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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Event;

use Pimcore\Model\User;
use Symfony\Contracts\EventDispatcher\Event;

final class LostPasswordEvent extends Event
{
    public const EVENT_NAME = 'pimcore.admin.login.lostpassword';

    protected bool $sendMail = true;

    public function __construct(private User $user, private string $loginUrl)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLoginUrl(): string
    {
        return $this->loginUrl;
    }

    /**
     * Determines if lost password mail should be sent
     */
    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    /**
     * Sets flag whether to send lost password mail or not
     */
    public function setSendMail(bool $sendMail): LostPasswordEvent
    {
        $this->sendMail = $sendMail;

        return $this;
    }
}