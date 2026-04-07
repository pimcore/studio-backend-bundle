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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\ObjectBrickDetail;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * @internal
 */
final readonly class DetailHydrator implements DetailHydratorInterface
{
    public function hydrate(Definition $definition): ObjectBrickDetail
    {
        return new ObjectBrickDetail(
            $definition->getKey() ?? '',
            $definition->getTitle(),
            $definition->getGroup(),
            $definition->getParentClass(),
            $definition->getImplementsInterfaces(),
            $definition->getBlockedVarsForExport(),
            $definition->isWritable(),
            $definition->getClassDefinitions(),
        );
    }
}
