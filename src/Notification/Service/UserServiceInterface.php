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

use _PHPStan_2132cc0bd\React\Dns\Model\Record;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Schema\Recipient;

/**
 * @internal
 */
interface UserServiceInterface
{
    /**
     * @throws UserNotFoundException
     *
     * @return Recipient[]
     */
    public function getRecipientsForCurrentUser(): array;
}