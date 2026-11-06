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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface MailServiceInterface
{
    public function sendResetPasswordMail(UserInterface $user, string $token, ?string $loginUrl = null): void;

    /**
     * @throws DomainConfigurationException|SendMailException
     */
    public function sendInvitationMail(UserInterface $user, string $invitationUrl): void;
}
