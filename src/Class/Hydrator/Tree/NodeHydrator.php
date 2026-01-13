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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\Tree;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionTreeNode;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;

/**
 * @internal
 */
final readonly class NodeHydrator implements NodeHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrate(ClassDefinition $class): ClassDefinitionTreeNode
    {
        $hasBrickField = false;
        foreach ($class->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Objectbricks) {
                $hasBrickField = true;

                break;
            }
        }

        return new ClassDefinitionTreeNode(
            $class->getId(),
            $class->getName(),
            $class->getTitle(),
            $this->iconService->getIconForClassDefinition($class->getIcon()),
            $class->getGroup(),
            $class->isEnableGridLocking(),
            $hasBrickField
        );
    }
}
