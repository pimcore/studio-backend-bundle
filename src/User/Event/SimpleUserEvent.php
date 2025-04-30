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

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;

final class SimpleUserEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.simple_user';

    public function __construct(private readonly SimpleUser $user)
    {
        parent::__construct($this->user);
    }

    public function getUser(): SimpleUser
    {
        return $this->user;
    }
}
