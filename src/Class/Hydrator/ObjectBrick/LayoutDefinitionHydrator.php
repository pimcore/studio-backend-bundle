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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;

/**
 * @internal
 */
final class LayoutDefinitionHydrator implements LayoutDefinitionHydratorInterface
{
    public function hydrate(ObjectBrickDefinition $data): LayoutDefinition
    {
        $layout = $data->getLayoutDefinitions();

        return new LayoutDefinition(
            $data->getKey(),
            $layout->getDatatype(),
            $layout->getName(),
            $layout->getType(),
            $layout->getRegion(),
            $layout->getTitle(),
            $layout->getWidth(),
            $layout->getHeight(),
            $layout->getCollapsible(),
            $layout->getCollapsed(),
            $layout->getChildren(),
        );
    }
}
