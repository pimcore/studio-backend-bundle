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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConfigLayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ConfigLayoutDefinitionEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.objectBrick.config_layout_definition';

    public function __construct(
        private readonly ConfigLayoutDefinition $configLayoutDefinition,
    ) {
        parent::__construct($this->configLayoutDefinition);
    }

    public function getConfigLayoutDefinition(): ConfigLayoutDefinition
    {
        return $this->configLayoutDefinition;
    }
}
