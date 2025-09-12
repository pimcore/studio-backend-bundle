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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\LayoutCompact;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

/**
 * @internal
 */
final readonly class CompactLayoutHydrator implements CompactLayoutHydratorInterface
{
    private const string MAIN_TYPE = 'main';

    private const string CUSTOM_TYPE = 'custom';

    private const int MAIN_LAYOUT_ID = 0;

    private const string ID_SEPARATOR = '_';

    public function hydrate(
        ClassDefinition $classDefinition,
        ?CustomLayout $layout = null
    ): LayoutCompact {
        if ($layout === null) {
            return $this->createMainLayout($classDefinition);
        }

        return $this->createCustomLayout($classDefinition, $layout);
    }

    private function createMainLayout(ClassDefinition $classDefinition): LayoutCompact
    {
        return new LayoutCompact(
            id: $this->buildLayoutId($classDefinition->getId(), self::MAIN_LAYOUT_ID),
            name: $classDefinition->getName(),
            type: self::MAIN_TYPE
        );
    }

    private function createCustomLayout(ClassDefinition $classDefinition, CustomLayout $layout): LayoutCompact
    {
        return new LayoutCompact(
            id: $this->buildLayoutId($classDefinition->getId(), $layout->getId() ?? ''),
            name: $this->buildCustomLayoutName($classDefinition->getName(), $layout->getName()),
            type: self::CUSTOM_TYPE
        );
    }

    private function buildLayoutId(string $classId, string|int $layoutId): string
    {
        return $classId . self::ID_SEPARATOR . $layoutId;
    }

    private function buildCustomLayoutName(string $className, string $layoutName): string
    {
        return $className . self::ID_SEPARATOR . $layoutName;
    }
}
