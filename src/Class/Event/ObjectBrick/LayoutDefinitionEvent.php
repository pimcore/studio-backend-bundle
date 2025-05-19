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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class LayoutDefinitionEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.objectBrick.layout_definition';

    public function __construct(private readonly LayoutDefinition $layoutDefinition)
    {
        parent::__construct($this->layoutDefinition);
    }

    public function getLayoutDefinition(): LayoutDefinition
    {
        return $this->layoutDefinition;
    }
}
