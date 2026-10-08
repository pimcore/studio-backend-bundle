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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TwoFactorSetup;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface TwoFactorServiceInterface
{
    /**
     * Creates a new secret and keeps it pending in the session; the user is not changed.
     */
    public function createSetup(UserInterface $user): TwoFactorSetup;

    /**
     * Enables two-factor authentication with the pending secret if the code matches it.
     *
     * @throws ConflictException when there is no pending secret for this user
     * @throws UnprocessableContentException when the code does not match
     * @throws DatabaseException
     */
    public function confirmSetup(UserInterface $user, string $code): void;

    /**
     * @throws ForbiddenException while two-factor authentication is required for the user
     * @throws DatabaseException
     */
    public function disable(UserInterface $user): void;
}
