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

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionDetail;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

/**
 * @internal
 */
final readonly class DetailHydrator implements DetailHydratorInterface
{
    public function hydrate(Definition $definition): FieldCollectionDetail
    {
        return new FieldCollectionDetail(
            $definition->getKey() ?? '',
            $definition->getTitle(),
            $definition->getGroup(),
            $definition->getParentClass(),
            $definition->getImplementsInterfaces(),
            $definition->getBlockedVarsForExport(),
            $definition->isWritable(),
        );
    }
}
