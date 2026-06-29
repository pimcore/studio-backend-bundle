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
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\ConfigurationType;

final class ConfigurationTypeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.ownership_management.configuration_type';

    public function __construct(private readonly ConfigurationType $configurationType)
    {
        parent::__construct($this->configurationType);
    }

    public function getConfigurationType(): ConfigurationType
    {
        return $this->configurationType;
    }
}
