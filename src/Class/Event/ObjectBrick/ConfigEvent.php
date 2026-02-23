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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickConfig;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.objectBrick.config';

    public function __construct(
        private readonly ObjectBrickConfig $objectBrickConfig
    ) {
        parent::__construct($objectBrickConfig);
    }

    public function getObjectBrickConfig(): ObjectBrickConfig
    {
        return $this->objectBrickConfig;
    }
}
