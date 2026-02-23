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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConfigLayoutDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;

/**
 * @internal
 */
final readonly class ConfigLayoutDefinitionHydrator implements ConfigLayoutDefinitionHydratorInterface
{
    public function hydrate(Panel $layout): ConfigLayoutDefinition
    {
        return new ConfigLayoutDefinition(
            $layout->getName(),
            $layout->getType(),
            $layout->getRegion(),
            $layout->getTitle(),
            (int) $layout->getWidth(),
            (int) $layout->getHeight(),
            $layout->getCollapsible(),
            $layout->getCollapsed(),
            $layout->getBodyStyle(),
            $layout->getDatatype(),
            $layout->getChildren(),
            $layout->getLocked(),
            'panel',
            $layout->getLayout(),
            $layout->getBorder(),
            $layout->getIcon(),
            $layout->getLabelWidth(),
            $layout->getLabelAlign(),
        );
    }
}
