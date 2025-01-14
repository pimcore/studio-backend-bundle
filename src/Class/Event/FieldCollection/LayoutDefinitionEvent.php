<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class LayoutDefinitionEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.field_collection.layout_definition';

    public function __construct(private readonly LayoutDefinition $layoutDefinition)
    {
        parent::__construct($this->layoutDefinition);
    }

    public function getLayoutDefinition(): LayoutDefinition
    {
        return $this->layoutDefinition;
    }
}
