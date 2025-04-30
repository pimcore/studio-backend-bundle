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

namespace Pimcore\Bundle\StudioBackendBundle\Role\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Role\Schema\DetailedRole;

final class DetailedRoleEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.user_detailed_role';

    public function __construct(private readonly DetailedRole $role)
    {
        parent::__construct($role);
    }

    public function getUserRole(): DetailedRole
    {
        return $this->role;
    }
}
