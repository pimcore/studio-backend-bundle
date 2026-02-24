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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrickUsageData;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class UsageDataEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.object_brick.usage_data';

    public function __construct(
        private readonly ObjectBrickUsageData $usageData
    ) {
        parent::__construct($usageData);
    }

    public function getUsageData(): ObjectBrickUsageData
    {
        return $this->usageData;
    }
}
