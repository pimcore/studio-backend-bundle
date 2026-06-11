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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\TokenLink;

/**
 * @internal
 */
interface UserLoginServiceInterface
{
    /**
     * @throws InvalidArgumentException|RateLimitException|DomainConfigurationException|SendMailException
     */
    public function resetPassword(ResetPassword $resetPassword): void;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getLoginTokenUrl(int $id, TokenLink $parameter): string;
}
