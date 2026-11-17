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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser;

final class ServiceUserEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.service_user';

    public function __construct(private readonly ServiceUser $serviceUser)
    {
        parent::__construct($this->serviceUser);
    }

    public function getServiceUser(): ServiceUser
    {
        return $this->serviceUser;
    }
}
