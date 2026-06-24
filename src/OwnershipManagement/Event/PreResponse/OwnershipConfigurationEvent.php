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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;

final class OwnershipConfigurationEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.ownership_management.ownership_configuration';

    public function __construct(private readonly OwnershipConfiguration $ownershipConfiguration)
    {
        parent::__construct($this->ownershipConfiguration);
    }

    public function getOwnershipConfiguration(): OwnershipConfiguration
    {
        return $this->ownershipConfiguration;
    }
}
