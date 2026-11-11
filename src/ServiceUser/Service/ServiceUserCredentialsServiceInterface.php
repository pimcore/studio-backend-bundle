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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserCredentials;
use Pimcore\Model\User;

/**
 * @internal
 */
interface ServiceUserCredentialsServiceInterface
{
    /**
     * @param list<string>|null $scopes null grants every registered scope
     *
     * @throws InvalidArgumentException
     */
    public function register(User $serviceUser, ?array $scopes): ServiceUserCredentials;

    /**
     * @throws NotFoundException
     */
    public function rotate(User $serviceUser): ServiceUserCredentials;
}
