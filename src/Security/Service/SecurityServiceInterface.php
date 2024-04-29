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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\Credentials;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotAuthorizedException;
use Pimcore\Model\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @internal
 */
interface SecurityServiceInterface
{
    /**
     * @throws AccessDeniedException
     */
    public function authenticateUser(Credentials $credentials): PasswordAuthenticatedUserInterface;

    public function checkAuthToken(string $token): bool;

    /**
     * @throws NotAuthorizedException
     */
    public function getCurrentUser(): UserInterface;
}
