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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\ConfigLayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\LayoutDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldCollectionDefinition;

/**
 * @internal
 */
final class LayoutDefinitionHydrator implements LayoutDefinitionHydratorInterface
{
    public function hydrate(FieldCollectionDefinition $data): LayoutDefinition
    {
        $layout = $data->getLayoutDefinitions();

        return new LayoutDefinition(
            $data->getKey(),
            $layout->getDatatype(),
            $data->getGroup(),
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

    public function hydrateConfigLayoutDefinition(Layout $layout): ConfigLayoutDefinition
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
            $layout->fieldtype ?? 'panel',
            method_exists($layout, 'getLayout') ? $layout->getLayout() : null,
            method_exists($layout, 'getBorder') ? $layout->getBorder() : false,
            method_exists($layout, 'getIcon') ? $layout->getIcon() : null,
            method_exists($layout, 'getLabelWidth') ? $layout->getLabelWidth() : 100,
            method_exists($layout, 'getLabelAlign') ? $layout->getLabelAlign() : 'left',
        );
    }
}
